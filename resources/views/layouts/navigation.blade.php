@php
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $dashboardRoute = $isAdmin ? 'admin.dashboard' : 'doctor.dashboard';
@endphp

<nav class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center space-x-8">
                <a href="{{ route($dashboardRoute) }}" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900">DoctorChat</span>
                </a>

                <div class="hidden md:flex items-center space-x-4">
                    @if($isAdmin)
                        <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900' }} rounded-lg transition-colors">
                            Dashboard
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.users.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900' }} rounded-lg transition-colors">
                            Users
                        </a>
                        <a href="{{ route('admin.guide.index') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.guide.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900' }} rounded-lg transition-colors">
                            Guide
                        </a>
                    @else
                        <a href="{{ route('doctor.dashboard') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('doctor.dashboard') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900' }} rounded-lg transition-colors">
                            Dashboard
                        </a>
                        <a href="{{ route('doctor.chat.index') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('doctor.chat.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900' }} rounded-lg transition-colors">
                            Chat
                        </a>
                        <a href="{{ route('doctor.files.index') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('doctor.files.*') ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:text-gray-900' }} rounded-lg transition-colors">
                            Files
                        </a>
                    @endif
                </div>
            </div>

            <div class="flex items-center">
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center space-x-2 text-sm text-gray-600">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-blue-600 font-semibold">{{ substr(auth()->user()->name, 0, 1) }}</span>
                        </div>
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
