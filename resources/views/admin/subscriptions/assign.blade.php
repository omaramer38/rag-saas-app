@extends('layouts.admin')

@section('title', 'Assign Subscription')
@section('header', 'Assign Subscription')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <form method="POST" action="{{ route('admin.subscriptions.store') }}">
            @csrf

            <div class="space-y-6">
                {{-- Select Doctor --}}
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">Select Doctor</label>
                    <select id="user_id" name="user_id" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Choose a doctor...</option>
                        @foreach(\App\Models\User::doctors()->active()->get() as $doctor)
                            <option value="{{ $doctor->id }}" {{ old('user_id') == $doctor->id ? 'selected' : '' }}>
                                {{ $doctor->name }} ({{ $doctor->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Select Plan --}}
                <div>
                    <label for="plan_id" class="block text-sm font-medium text-gray-700 mb-1">Subscription Plan</label>
                    <select id="plan_id" name="plan_id" required
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Choose a plan...</option>
                        @foreach(\App\Models\SubscriptionPlan::active()->ordered()->get() as $plan)
                            <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} - {{ number_format($plan->price, 0) }} EGP ({{ $plan->duration_days }} days)
                            </option>
                        @endforeach
                    </select>
                    @error('plan_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Duration --}}
                <div>
                    <label for="duration_days" class="block text-sm font-medium text-gray-700 mb-1">Duration (Days)</label>
                    <input type="number" id="duration_days" name="duration_days" value="{{ old('duration_days', 30) }}" min="1" max="3650"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-sm text-gray-500 mt-1">How many days the subscription will last</p>
                    @error('duration_days') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Start Date --}}
                <div class="flex items-center">
                    <input type="checkbox" id="starts_now" name="starts_now" value="1" {{ old('starts_now', true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="starts_now" class="ml-2 text-sm text-gray-700">Start immediately</label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 mt-8">
                <a href="{{ route('admin.subscriptions.index') }}" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-colors">
                    Assign Subscription
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
