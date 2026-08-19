@extends('layouts.admin')

@section('title', 'Subscription Plans')
@section('header', 'Plans')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <p class="text-gray-600">Manage subscription plans</p>
        <a href="{{ route('admin.plans.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-colors">
            + Add Plan
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($plans as $plan)
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $plan->name }}</h3>
                    @if($plan->is_active)
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">Active</span>
                    @else
                        <span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs font-medium rounded-full">Inactive</span>
                    @endif
                </div>

                <div class="mb-4">
                    <span class="text-3xl font-bold text-gray-900">{{ number_format($plan->price, 0) }}</span>
                    <span class="text-gray-500">EGP / {{ $plan->duration_days }} days</span>
                </div>

                <p class="text-sm text-gray-600 mb-4">{{ $plan->description ?? 'No description' }}</p>

                <p class="text-sm text-gray-500 mb-4">{{ $plan->subscriptions_count }} subscriptions</p>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('admin.plans.edit', $plan) }}" class="flex-1 text-center px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors text-sm">Edit</a>
                    <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-colors text-sm">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500">No plans created yet.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
