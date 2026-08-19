<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymobController extends Controller
{
    public function callback(Request $request)
    {
        // This handles the user redirect after payment
        $success = $request->boolean('success');

        if ($success) {
            return redirect()->route('payment.success');
        }

        return redirect()->route('payment.fail');
    }

    public function success()
    {
        return view('payment.success');
    }

    public function fail()
    {
        return view('payment.fail');
    }
}
