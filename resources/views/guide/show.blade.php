@extends('layouts.landing')

@section('title', $page->title . ' - DoctorChat Guide')

@section('content')
<div class="pt-24 pb-20 px-4">
    <div class="max-w-3xl mx-auto">
        {{-- Breadcrumb --}}
        <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-8">
            <a href="{{ route('landing') }}" class="hover:text-gray-700">Home</a>
            <span>/</span>
            <a href="{{ route('guide.index') }}" class="hover:text-gray-700">Guide</a>
            <span>/</span>
            <span class="text-gray-900">{{ $page->title }}</span>
        </nav>

        {{-- Page Content --}}
        <article class="bg-white rounded-2xl border border-gray-100 p-8">
            @if($page->category)
                <span class="text-xs font-medium text-blue-600 uppercase">{{ $page->category }}</span>
            @endif

            <h1 class="text-3xl font-bold text-gray-900 mt-2 mb-6">{{ $page->title }}</h1>

            <div class="prose prose-lg max-w-none text-gray-700">
                {!! $page->content !!}
            </div>
        </article>

        {{-- Back Link --}}
        <div class="mt-8">
            <a href="{{ route('guide.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Guide
            </a>
        </div>
    </div>
</div>
@endsection
