@extends('layouts.app')

@section('title', 'Subscription Plans - DoctorChat')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="text-center mb-12">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Choose Your Plan</h1>
        <p class="text-xl text-gray-600">Select the plan that works best for you</p>
    </div>

    {{-- Current Subscription --}}
    @if($currentSubscription)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-green-800">Current Plan: {{ $currentSubscription->plan->name }}</h3>
                    <p class="text-green-700">Active until {{ $currentSubscription->expires_at->format('M d, Y') }}</p>
                </div>
                <span class="px-4 py-2 bg-green-100 text-green-700 rounded-full font-medium">Active</span>
            </div>
        </div>
    @endif

    {{-- Plans Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-{{ count($plans) > 0 ? count($plans) : 3 }} gap-8">
        @forelse($plans as $index => $plan)
            <div class="rounded-2xl border {{ $index === 1 ? 'border-blue-600 shadow-xl' : 'border-gray-200' }} p-8 relative">
                @if($index === 1)
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-blue-600 text-white px-4 py-1 rounded-full text-sm font-medium">
                        Popular
                    </div>
                @endif

                <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $plan->name }}</h3>
                <div class="mb-4">
                    <span class="text-4xl font-bold text-gray-900">{{ number_format($plan->price, 0) }}</span>
                    <span class="text-gray-500">EGP / {{ $plan->duration_label }}</span>
                </div>

                <p class="text-gray-600 mb-6">{{ $plan->description }}</p>

                @if($plan->features)
                    <ul class="space-y-3 mb-8">
                        @foreach($plan->features as $feature)
                            <li class="flex items-center text-gray-600">
                                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if($currentSubscription && $currentSubscription->plan_id === $plan->id)
                    <button disabled class="block w-full text-center py-3 rounded-xl font-semibold bg-gray-100 text-gray-500 cursor-not-allowed">
                        Current Plan
                    </button>
                @else
                    <form action="{{ route('doctor.subscribe', $plan) }}" method="POST">
                        @csrf
                        <button type="submit" class="block w-full text-center py-3 rounded-xl font-semibold {{ $index === 1 ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }} transition-all">
                            Subscribe Now
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500">No plans available at the moment. Please check back later.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
