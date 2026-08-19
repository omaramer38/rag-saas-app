<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Models\UserSubscription;
use App\Services\PaymobService;
use App\Services\SubscriptionService;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request, PaymobService $paymob)
    {
        try {
            $data = $request->all();
            $hmac = $request->header('X-Hmac-Signature', '');

            // Verify HMAC signature
            if (!$paymob->verifyWebhookHmac($data, $hmac)) {
                Log::warning('Paymob webhook HMAC verification failed');
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $orderId = $data['order']['id'] ?? null;
            $success = $data['success'] ?? false;

            if (!$orderId) {
                return response()->json(['error' => 'Missing order ID'], 400);
            }

            // Find the transaction
            $transaction = PaymentTransaction::where('paymob_order_id', $orderId)->first();

            if (!$transaction) {
                Log::warning('Paymob webhook: transaction not found', ['order_id' => $orderId]);
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            if ($success) {
                // Payment successful - activate subscription
                $subscription = $transaction->subscription;

                if ($subscription && $subscription->status === 'pending') {
                    $subscriptionService = app(SubscriptionService::class);
                    $subscriptionService->activateSubscription(
                        $transaction->user,
                        $subscription->plan,
                        $transaction
                    );

                    Log::info('Paymob payment successful', [
                        'order_id' => $orderId,
                        'user_id' => $transaction->user_id,
                    ]);
                }
            } else {
                // Payment failed
                $transaction->update([
                    'status' => 'failed',
                    'metadata' => $data,
                ]);

                // Update subscription status
                if ($transaction->subscription) {
                    $transaction->subscription->update(['status' => 'cancelled']);
                }

                Log::info('Paymob payment failed', [
                    'order_id' => $orderId,
                    'user_id' => $transaction->user_id,
                ]);
            }

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Paymob webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
