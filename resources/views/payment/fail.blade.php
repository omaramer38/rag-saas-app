@extends('layouts.landing')

@section('title', 'Payment Failed')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="max-w-md text-center">
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Payment Failed</h1>
        <p class="text-gray-600 mb-8">Unfortunately, your payment could not be processed. Please try again.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('doctor.plans') }}" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-blue-700 transition-colors">
                Try Again
            </a>
            <a href="{{ route('doctor.dashboard') }}" class="bg-gray-100 text-gray-700 px-8 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-colors">
                Go to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
