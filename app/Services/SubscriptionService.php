<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Notification;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    private const CACHE_TTL = 300;

    /**
     * Check if user has an active subscription (cached per user)
     */
    public function hasActiveSubscription(User $user): bool
    {
        return Cache::remember("user_{$user->id}_has_subscription", self::CACHE_TTL, function () use ($user) {
            return $user->subscriptions()
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->exists();
        });
    }

    /**
     * Get user's active subscription (cached per user)
     */
    public function getActiveSubscription(User $user): ?UserSubscription
    {
        return Cache::remember("user_{$user->id}_active_subscription", self::CACHE_TTL, function () use ($user) {
            return $user->subscriptions()
                ->with('plan')
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->latest()
                ->first();
        });
    }

    /**
     * Activate a subscription after successful payment
     */
    public function activateSubscription(
        User $user,
        SubscriptionPlan $plan,
        PaymentTransaction $transaction
    ): UserSubscription {
        return DB::transaction(function () use ($user, $plan, $transaction) {
            // Cancel any existing active subscription
            $user->subscriptions()
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);

            // Create new subscription
            $subscription = UserSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => now()->addDays($plan->duration_days),
                'payment_reference' => $transaction->paymob_order_id,
            ]);

            // Link transaction to subscription
            $transaction->update([
                'subscription_id' => $subscription->id,
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            // Clear user subscription cache
            $this->clearUserCache($user->id);

            // Send notification
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Subscription Activated',
                'message' => "Your {$plan->name} subscription is now active until {$subscription->expires_at->format('M d, Y')}.",
                'type' => 'success',
            ]);

            // Log activity
            ActivityLog::log('subscription_activated', $subscription, [
                'plan' => $plan->name,
                'amount' => $transaction->amount,
            ]);

            return $subscription;
        });
    }

    /**
     * Check and expire old subscriptions
     */
    public function expireOldSubscriptions(): int
    {
        $expired = UserSubscription::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        if ($expired > 0) {
            ActivityLog::log('subscriptions_expired', null, ['count' => $expired]);
            // Clear affected user caches
            UserSubscription::where('status', 'expired')
                ->where('expires_at', '<=', now())
                ->pluck('user_id')
                ->each(fn($userId) => $this->clearUserCache($userId));
        }

        return $expired;
    }

    /**
     * Get subscription statistics for admin (cached)
     */
    public function getStats(): array
    {
        return Cache::remember('admin_subscription_stats', self::CACHE_TTL, function () {
            return [
                'active' => UserSubscription::where('status', 'active')
                    ->where('expires_at', '>', now())
                    ->count(),
                'expired' => UserSubscription::where('status', 'expired')->count(),
                'cancelled' => UserSubscription::where('status', 'cancelled')->count(),
                'total_revenue' => PaymentTransaction::where('status', 'completed')
                    ->sum('amount'),
                'monthly_revenue' => PaymentTransaction::where('status', 'completed')
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('amount'),
            ];
        });
    }

    /**
     * Clear cache for a specific user
     */
    public function clearUserCache(int $userId): void
    {
        Cache::forget("user_{$userId}_has_subscription");
        Cache::forget("user_{$userId}_active_subscription");
    }

    /**
     * Clear all subscription-related caches
     */
    public static function clearAllCache(): void
    {
        Cache::forget('admin_subscription_stats');
        Cache::forget('admin_dashboard_stats');
        Cache::forget('active_plans');
    }
}
