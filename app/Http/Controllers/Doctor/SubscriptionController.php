<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\PaymobService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function plans()
    {
        $plans = SubscriptionPlan::active()->ordered()->get();
        $currentSubscription = (new SubscriptionService())->getActiveSubscription(auth()->user());

        return view('doctor.subscription.plans', compact('plans', 'currentSubscription'));
    }

    public function subscribe(SubscriptionPlan $plan, PaymobService $paymob)
    {
        $user = auth()->user();

        if (!$plan->is_active) {
            return redirect()->route('doctor.plans')
                ->with('error', 'This plan is no longer available.');
        }

        try {
            // Create Paymob order
            $orderId = $paymob->createOrder($plan->price);

            // Create pending subscription
            $subscription = UserSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
            ]);

            // Create payment transaction
            $transaction = $paymob->createTransaction($user, $subscription, $plan->price, $orderId);

            // Get payment key
            $paymentKey = $paymob->getPaymentKey($orderId, $user, $plan->price);

            // Redirect to Paymob iframe
            $iframeUrl = $paymob->getIframeUrl($paymentKey);

            return redirect($iframeUrl);

        } catch (\Exception $e) {
            return redirect()->route('doctor.plans')
                ->with('error', 'Payment initialization failed. Please try again.');
        }
    }
}
