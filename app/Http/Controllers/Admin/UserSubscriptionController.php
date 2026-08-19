<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Services\StatisticsService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class UserSubscriptionController extends Controller
{
    public function create()
    {
        $doctors = User::doctors()->active()->get();
        $plans = SubscriptionPlan::active()->ordered()->get();

        return view('admin.subscriptions.assign', compact('doctors', 'plans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:subscription_plans,id',
            'duration_days' => 'required|integer|min:1|max:3650',
            'starts_now' => 'boolean',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        // Cancel existing active subscription
        $user->subscriptions()
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $startsAt = ($validated['starts_now'] ?? true) ? now() : now()->addDay();
        $expiresAt = $startsAt->copy()->addDays((int) $validated['duration_days']);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'started_at' => $startsAt,
            'expires_at' => $expiresAt,
            'payment_reference' => 'admin-assigned',
        ]);

        // Notify user
        Notification::create([
            'user_id' => $user->id,
            'title' => 'Subscription Assigned',
            'message' => "An admin has assigned you a {$plan->name} subscription until {$expiresAt->format('M d, Y')}.",
            'type' => 'success',
        ]);

        ActivityLog::log('subscription_assigned', $subscription, [
            'admin_id' => auth()->id(),
            'user_id' => $user->id,
            'plan' => $plan->name,
        ]);

        // Clear caches
        (new SubscriptionService())->clearUserCache($user->id);
        StatisticsService::clearCache();

        return redirect()->route('admin.subscriptions.index')
            ->with('success', "Subscription assigned to {$user->name} successfully.");
    }

    public function index(Request $request)
    {
        $query = UserSubscription::with(['user', 'plan']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->latest()->paginate(15);

        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    public function assign(Request $request, User $user)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'duration_days' => 'required|integer|min:1',
            'starts_now' => 'boolean',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        // Cancel existing active subscription
        $user->subscriptions()
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $startsAt = ($validated['starts_now'] ?? true) ? now() : now()->addDay();
        $expiresAt = $startsAt->copy()->addDays((int) $validated['duration_days']);

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $validated['plan_id'],
            'status' => 'active',
            'started_at' => $startsAt,
            'expires_at' => $expiresAt,
            'payment_reference' => 'admin-assigned',
        ]);

        // Notify user
        Notification::create([
            'user_id' => $user->id,
            'title' => 'Subscription Assigned',
            'message' => "An admin has assigned you a {$plan->name} subscription until {$expiresAt->format('M d, Y')}.",
            'type' => 'success',
        ]);

        ActivityLog::log('subscription_assigned', $subscription, [
            'admin_id' => auth()->id(),
            'user_id' => $user->id,
            'plan' => $plan->name,
        ]);

        // Clear caches
        (new SubscriptionService())->clearUserCache($user->id);
        StatisticsService::clearCache();

        return redirect()->route('admin.subscriptions.index')
            ->with('success', "Subscription assigned to {$user->name} successfully.");
    }
}
