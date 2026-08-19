<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DoctorChat Dashboard')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="min-h-screen">
        {{-- Top Navigation --}}
        <nav class="bg-white border-b border-gray-100 fixed top-0 left-0 right-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    {{-- Logo & Nav Links --}}
                    <div class="flex items-center space-x-8">
                        <a href="{{ route('doctor.dashboard') }}" class="flex items-center space-x-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                            </div>
                            <span class="text-xl font-bold text-gray-900">DoctorChat</span>
                        </a>

                        <div class="hidden md:flex items-center space-x-4">
                            <a href="{{ route('doctor.dashboard') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('doctor.dashboard') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900' }} rounded-lg transition-colors">
                                Dashboard
                            </a>
                            <a href="{{ route('doctor.chat.index') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('doctor.chat.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900' }} rounded-lg transition-colors">
                                Chat
                            </a>
                            <a href="{{ route('doctor.files.index') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('doctor.files.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900' }} rounded-lg transition-colors">
                                Files
                            </a>
                            <a href="{{ route('doctor.plans') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('doctor.plans') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900' }} rounded-lg transition-colors">
                                Subscription
                            </a>
                        </div>
                    </div>

                    {{-- User Dropdown --}}
                    <div class="flex items-center">
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center space-x-3 text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 font-semibold">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                </div>
                                <span class="hidden md:block">{{ auth()->user()->name }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-transition
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
                                <hr class="my-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        {{-- Page Content --}}
        <div class="pt-16">
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            @if(session('warning'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-xl">
                        {{ session('warning') }}
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
