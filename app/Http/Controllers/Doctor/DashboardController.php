<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorFile;
use App\Models\ChatSession;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $subscriptionService = app(SubscriptionService::class);

        $subscription = $subscriptionService->getActiveSubscription($user);
        $files = $user->files()->latest()->get();
        $recentSessions = $user->chatSessions()
            ->with('lastMessage')
            ->latest()
            ->limit(5)
            ->get();
        $totalMessages = $user->chatSessions()
            ->has('messages')
            ->withCount('messages')
            ->get()
            ->sum('messages_count');

        return view('doctor.dashboard', compact(
            'subscription',
            'files',
            'recentSessions',
            'totalMessages'
        ));
    }
}
