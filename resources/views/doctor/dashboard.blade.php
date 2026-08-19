@extends('layouts.app')

@section('title', 'Dashboard - DoctorChat')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Welcome Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ auth()->user()->name }}!</h1>
        <p class="text-gray-600">Here's what's happening with your account.</p>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        {{-- Subscription Status --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                @if($subscription)
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">Active</span>
                @else
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs font-medium rounded-full">No Plan</span>
                @endif
            </div>
            <p class="text-sm text-gray-500">Subscription</p>
            @if($subscription)
                <p class="text-lg font-semibold text-gray-900">{{ $subscription->plan->name }}</p>
                <p class="text-xs text-gray-500 mt-1">Expires {{ $subscription->expires_at->format('M d, Y') }}</p>
            @else
                <a href="{{ route('doctor.plans') }}" class="text-sm text-blue-600 hover:text-blue-700">Choose a plan →</a>
            @endif
        </div>

        {{-- Files --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500">Uploaded Files</p>
            <p class="text-lg font-semibold text-gray-900">{{ $files->count() }}</p>
            <a href="{{ route('doctor.files.index') }}" class="text-sm text-blue-600 hover:text-blue-700">Manage files →</a>
        </div>

        {{-- Chat Sessions --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500">Chat Sessions</p>
            <p class="text-lg font-semibold text-gray-900">{{ $recentSessions->count() }}</p>
            <a href="{{ route('doctor.chat.index') }}" class="text-sm text-blue-600 hover:text-blue-700">Start chatting →</a>
        </div>

        {{-- Total Messages --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500">Total Messages</p>
            <p class="text-lg font-semibold text-gray-900">{{ $totalMessages }}</p>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- New Chat --}}
        <a href="{{ route('doctor.chat.index') }}" class="bg-white rounded-2xl border border-gray-100 p-6 hover:border-blue-200 hover:shadow-md transition-all group">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Start New Chat</h3>
                    <p class="text-sm text-gray-500">Have a conversation with your AI assistant</p>
                </div>
            </div>
        </a>

        {{-- Upload File --}}
        <a href="{{ route('doctor.files.index') }}" class="bg-white rounded-2xl border border-gray-100 p-6 hover:border-green-200 hover:shadow-md transition-all group">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center group-hover:bg-green-200 transition-colors">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Upload Research</h3>
                    <p class="text-sm text-gray-500">Upload a new PDF to train your chatbot</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Recent Chat Sessions --}}
    @if($recentSessions->count() > 0)
        <div class="mt-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Chats</h2>
            <div class="bg-white rounded-2xl border border-gray-100 divide-y divide-gray-100">
                @foreach($recentSessions as $session)
                    <a href="{{ route('doctor.chat.show', $session) }}" class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ $session->title }}</p>
                                @if($session->lastMessage)
                                    <p class="text-sm text-gray-500 truncate max-w-md">{{ $session->lastMessage->content }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="text-sm text-gray-400">{{ $session->created_at->diffForHumans() }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
