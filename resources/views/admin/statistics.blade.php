@extends('layouts.admin')

@section('title', 'Statistics')
@section('header', 'Statistics')

@section('content')
<div class="space-y-6">
    {{-- Overview Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <p class="text-sm text-gray-500">Total Doctors</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['users']['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <p class="text-sm text-gray-500">Active Users</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['users']['active'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <p class="text-sm text-gray-500">Total Files</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['files']['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <p class="text-sm text-gray-500">Total Messages</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['chat']['total_messages']) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Revenue --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Revenue</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Revenue</span>
                    <span class="font-semibold text-gray-900">{{ number_format($stats['subscriptions']['total_revenue'], 0) }} EGP</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">This Month</span>
                    <span class="font-semibold text-gray-900">{{ number_format($stats['subscriptions']['monthly_revenue'], 0) }} EGP</span>
                </div>
            </div>
        </div>

        {{-- Subscriptions --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Subscriptions</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Active</span>
                    <span class="font-semibold text-green-600">{{ $stats['subscriptions']['active'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Expired</span>
                    <span class="font-semibold text-red-600">{{ $stats['subscriptions']['expired'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Cancelled</span>
                    <span class="font-semibold text-gray-500">{{ $stats['subscriptions']['cancelled'] }}</span>
                </div>
            </div>
        </div>

        {{-- Chat Stats --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Chat Activity</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Sessions</span>
                    <span class="font-semibold text-gray-900">{{ number_format($stats['chat']['total_sessions']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Messages Today</span>
                    <span class="font-semibold text-gray-900">{{ number_format($stats['chat']['messages_today']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Avg Response Time</span>
                    <span class="font-semibold text-gray-900">{{ number_format($stats['chat']['avg_response_time'] ?? 0, 0) }}ms</span>
                </div>
            </div>
        </div>

        {{-- Files Stats --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Files</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Ready</span>
                    <span class="font-semibold text-green-600">{{ $stats['files']['ready'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Processing</span>
                    <span class="font-semibold text-yellow-600">{{ $stats['files']['processing'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Failed</span>
                    <span class="font-semibold text-red-600">{{ $stats['files']['failed'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
