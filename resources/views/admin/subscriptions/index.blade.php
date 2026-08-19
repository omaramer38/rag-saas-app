@extends('layouts.admin')

@section('title', 'Subscriptions')
@section('header', 'Subscriptions')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <p class="text-gray-600">Manage doctor subscriptions</p>
        <a href="{{ route('admin.subscriptions.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-colors">
            + Assign Subscription
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4">
        <form method="GET" class="flex items-center space-x-4">
            <select name="status" class="px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors">Filter</button>
        </form>
    </div>

    {{-- Subscriptions Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Started</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($subscriptions as $sub)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $sub->user->name ?? 'Deleted' }}</p>
                            <p class="text-sm text-gray-500">{{ $sub->user->email ?? '' }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $sub->plan->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4">
                            @if($sub->status === 'active')
                                <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">Active</span>
                            @elseif($sub->status === 'expired')
                                <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded-full">Expired</span>
                            @elseif($sub->status === 'cancelled')
                                <span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs font-medium rounded-full">Cancelled</span>
                            @else
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs font-medium rounded-full">Pending</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $sub->started_at?->format('M d, Y') ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $sub->expires_at?->format('M d, Y') ?? '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            @if($sub->user)
                                <form action="{{ route('admin.subscriptions.assign', $sub->user) }}" method="POST" class="inline-flex">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $sub->plan_id }}">
                                    <input type="hidden" name="duration_days" value="{{ $sub->plan->duration_days ?? 30 }}">
                                    <input type="hidden" name="starts_now" value="1">
                                    <button type="submit" class="text-blue-600 hover:text-blue-700 text-sm" title="Extend subscription">Extend</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">No subscriptions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-gray-100">
            {{ $subscriptions->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
