@extends('layouts.landing')

@section('title', 'Payment Successful')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md text-center">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Successful!</h1>
        <p class="text-gray-600 mb-8">Your subscription has been activated. You can now start using all the features.</p>
        <a href="{{ route('doctor.dashboard') }}" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-blue-700 transition-colors">
            Go to Dashboard
        </a>
    </div>
</div>
@endsection
