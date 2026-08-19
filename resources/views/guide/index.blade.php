@extends('layouts.landing')

@section('title', 'User Guide - DoctorChat')

@section('content')
<div class="pt-24 pb-20 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">User Guide</h1>
            <p class="text-xl text-gray-600">Learn how to use DoctorChat effectively</p>
        </div>

        {{-- Categories --}}
        @if($categories->count() > 0)
            <div class="flex flex-wrap gap-2 justify-center mb-8">
                @foreach($categories as $category)
                    <span class="px-4 py-2 bg-blue-50 text-blue-700 rounded-full text-sm font-medium">{{ $category }}</span>
                @endforeach
            </div>
        @endif

        {{-- Pages --}}
        <div class="space-y-4">
            @forelse($pages as $page)
                <a href="{{ route('guide.show', $page->slug) }}" class="block bg-white rounded-2xl border border-gray-100 p-6 hover:border-blue-200 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            @if($page->category)
                                <span class="text-xs font-medium text-blue-600 uppercase">{{ $page->category }}</span>
                            @endif
                            <h2 class="text-xl font-semibold text-gray-900 mt-1">{{ $page->title }}</h2>
                            <p class="text-gray-600 mt-2">{{ $page->excerpt }}</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            @empty
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <p class="text-gray-500 text-lg">No guide pages available yet.</p>
                    <p class="text-gray-400 mt-1">Check back later for documentation.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
