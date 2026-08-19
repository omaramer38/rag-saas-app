<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActiveSubscriptionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $subscriptionService = app(SubscriptionService::class);

        if (!$subscriptionService->hasActiveSubscription(auth()->user())) {
            return redirect()->route('doctor.plans')->with('warning', 'You need an active subscription to access this feature.');
        }

        return $next($request);
    }
}
