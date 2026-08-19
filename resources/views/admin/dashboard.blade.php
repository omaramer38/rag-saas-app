@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Total Users --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Doctors</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['users']['total'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-green-600 mt-2">+{{ $stats['users']['new_this_month'] }} this month</p>
        </div>

        {{-- Active Subscriptions --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Active Subscriptions</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['subscriptions']['active'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-2">{{ $stats['subscriptions']['expired'] }} expired</p>
        </div>

        {{-- Total Revenue --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Total Revenue</p>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['subscriptions']['total_revenue'], 0) }} EGP</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-2">{{ number_format($stats['subscriptions']['monthly_revenue'], 0) }} EGP this month</p>
        </div>

        {{-- RAG Health --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">RAG System</p>
                    <p class="text-3xl font-bold {{ $ragHealth ? 'text-green-600' : 'text-red-600' }}">
                        {{ $ragHealth ? 'Healthy' : 'Offline' }}
                    </p>
                </div>
                <div class="w-12 h-12 {{ $ragHealth ? 'bg-green-100' : 'bg-red-100' }} rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 {{ $ragHealth ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-2">{{ $ragStats['total_vectors'] ?? 0 }} vectors indexed</p>
        </div>
    </div>

    {{-- RAG System Details --}}
    @if($ragStats)
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">🤖 RAG System Metrics</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center p-4 bg-gray-50 rounded-xl">
                    <p class="text-3xl font-bold text-blue-600">{{ $ragStats['total_collections'] ?? 0 }}</p>
                    <p class="text-sm text-gray-500 mt-1">User Collections</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-xl">
                    <p class="text-3xl font-bold text-green-600">{{ number_format($ragStats['total_vectors'] ?? 0) }}</p>
                    <p class="text-sm text-gray-500 mt-1">Total Vectors</p>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-xl">
                    <p class="text-3xl font-bold text-purple-600">{{ $ragStats['qdrant_host'] ?? 'N/A' }}</p>
                    <p class="text-sm text-gray-500 mt-1">Qdrant Host</p>
                </div>
            </div>

            {{-- Collection Details --}}
            @if(isset($ragStats['collections']) && count($ragStats['collections']) > 0)
                <div class="mt-6">
                    <h4 class="font-medium text-gray-700 mb-3">User Collections</h4>
                    <div class="space-y-2">
                        @foreach($ragStats['collections'] as $collection)
                            @if(str_starts_with($collection['name'], 'user_'))
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <span class="text-blue-600 text-xs font-bold">
                                                {{ explode('_', $collection['name'])[1] }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $collection['name'] }}</p>
                                            <p class="text-xs text-gray-500">User #{{ explode('_', $collection['name'])[1] }}</p>
                                        </div>
                                    </div>
                                    <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                        {{ number_format($collection['vectors']) }} vectors
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('admin.users.create') }}" class="bg-white rounded-2xl border border-gray-100 p-6 hover:border-blue-200 hover:shadow-md transition-all">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Add User</h3>
                    <p class="text-sm text-gray-500">Create a new doctor account</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.plans.create') }}" class="bg-white rounded-2xl border border-gray-100 p-6 hover:border-green-200 hover:shadow-md transition-all">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Create Plan</h3>
                    <p class="text-sm text-gray-500">Add a new subscription plan</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.guide.create') }}" class="bg-white rounded-2xl border border-gray-100 p-6 hover:border-purple-200 hover:shadow-md transition-all">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Add Guide Page</h3>
                    <p class="text-sm text-gray-500">Create documentation for users</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Recent Users --}}
    <div class="bg-white rounded-2xl border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Recent Users</h3>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 hover:text-blue-700">View all →</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse(\App\Models\User::doctors()->latest()->limit(5)->get() as $user)
                <div class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-blue-600 font-semibold">{{ substr($user->name, 0, 1) }}</span>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-sm text-gray-500">{{ $user->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($user->is_active)
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">Active</span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded-full">Inactive</span>
                        @endif
                        <span class="text-sm text-gray-400">{{ $user->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-gray-500">
                    No users yet.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
