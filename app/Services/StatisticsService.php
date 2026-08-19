<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\DoctorFile;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get comprehensive dashboard statistics (cached)
     */
    public function getDashboardStats(): array
    {
        return Cache::remember('admin_dashboard_stats', self::CACHE_TTL, function () {
            return [
                'users' => $this->getUserStats(),
                'subscriptions' => (new SubscriptionService())->getStats(),
                'files' => $this->getFileStats(),
                'chat' => $this->getChatStats(),
                'revenue' => $this->getRevenueStats(),
            ];
        });
    }

    /**
     * Get user statistics
     */
    public function getUserStats(): array
    {
        $totalUsers = User::doctors()->count();
        $activeUsers = User::doctors()->active()->count();
        $newThisMonth = User::doctors()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $registrationsByMonth = User::doctors()
            ->select(DB::raw('COUNT(*) as count'), DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'))
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->get();

        return [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'new_this_month' => $newThisMonth,
            'registrations_chart' => $registrationsByMonth,
        ];
    }

    /**
     * Get file statistics
     */
    public function getFileStats(): array
    {
        return [
            'total' => DoctorFile::count(),
            'ready' => DoctorFile::where('status', 'ready')->count(),
            'processing' => DoctorFile::where('status', 'processing')->count(),
            'failed' => DoctorFile::where('status', 'failed')->count(),
            'total_size' => DoctorFile::sum('file_size'),
        ];
    }

    /**
     * Get chat statistics
     */
    public function getChatStats(): array
    {
        return [
            'total_sessions' => ChatSession::count(),
            'total_messages' => ChatMessage::count(),
            'messages_today' => ChatMessage::whereDate('created_at', today())->count(),
            'avg_response_time' => ChatMessage::where('role', 'assistant')
                ->whereNotNull('response_time_ms')
                ->avg('response_time_ms'),
        ];
    }

    /**
     * Get revenue statistics
     */
    public function getRevenueStats(): array
    {
        $revenueByMonth = PaymentTransaction::where('status', 'completed')
            ->select(
                DB::raw('SUM(amount) as total'),
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->get();

        return [
            'total' => PaymentTransaction::where('status', 'completed')->sum('amount'),
            'this_month' => PaymentTransaction::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'chart' => $revenueByMonth,
        ];
    }

    /**
     * Clear all statistics cache
     */
    public static function clearCache(): void
    {
        Cache::forget('admin_dashboard_stats');
        Cache::forget('admin_subscription_stats');
    }
}
