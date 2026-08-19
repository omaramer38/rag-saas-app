@extends('layouts.landing')

@section('title', 'DoctorChat - AI-Powered Medical Assistant')
@section('description', 'Upload your medical research and let AI help your practice with intelligent chatbots.')

@section('content')
{{-- Hero Section --}}
<section class="pt-32 pb-20 px-4">
    <div class="max-w-7xl mx-auto text-center">
        <div class="inline-flex items-center px-4 py-2 bg-blue-50 rounded-full mb-6">
            <span class="w-2 h-2 bg-blue-500 rounded-full mr-2 animate-pulse"></span>
            <span class="text-blue-700 text-sm font-medium">Now Available</span>
        </div>

        <h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-6 leading-tight">
            AI-Powered Chatbot for
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">
                Medical Professionals
            </span>
        </h1>

        <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-10">
            Upload your medical research, let our AI learn from it, and have an intelligent assistant
            ready to answer questions 24/7.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="bg-blue-600 text-white px-8 py-4 rounded-xl text-lg font-semibold hover:bg-blue-700 transition-all shadow-lg hover:shadow-xl">
                Start Free Trial
            </a>
            <a href="#features" class="bg-white text-gray-700 px-8 py-4 rounded-xl text-lg font-semibold border border-gray-200 hover:border-gray-300 transition-all">
                Learn More
            </a>
        </div>
    </div>
</section>

{{-- Features Section --}}
<section id="features" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Everything You Need</h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Powerful features designed specifically for medical professionals
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Feature 1 --}}
            <div class="p-8 rounded-2xl border border-gray-100 hover:border-blue-200 hover:shadow-lg transition-all">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Upload Research</h3>
                <p class="text-gray-600">
                    Upload your PDF research papers, and our AI will learn from them to provide accurate answers.
                </p>
            </div>

            {{-- Feature 2 --}}
            <div class="p-8 rounded-2xl border border-gray-100 hover:border-blue-200 hover:shadow-lg transition-all">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Intelligent Chat</h3>
                <p class="text-gray-600">
                    Have natural conversations with your AI assistant. It understands context and provides relevant answers.
                </p>
            </div>

            {{-- Feature 3 --}}
            <div class="p-8 rounded-2xl border border-gray-100 hover:border-blue-200 hover:shadow-lg transition-all">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Secure & Private</h3>
                <p class="text-gray-600">
                    Your data is encrypted and secure. Only you can access your research and chat history.
                </p>
            </div>

            {{-- Feature 4 --}}
            <div class="p-8 rounded-2xl border border-gray-100 hover:border-blue-200 hover:shadow-lg transition-all">
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Fast Responses</h3>
                <p class="text-gray-600">
                    Get instant answers powered by advanced AI. No waiting, no delays.
                </p>
            </div>

            {{-- Feature 5 --}}
            <div class="p-8 rounded-2xl border border-gray-100 hover:border-blue-200 hover:shadow-lg transition-all">
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Easy Updates</h3>
                <p class="text-gray-600">
                    Upload new research anytime. Our AI automatically updates its knowledge base.
                </p>
            </div>

            {{-- Feature 6 --}}
            <div class="p-8 rounded-2xl border border-gray-100 hover:border-blue-200 hover:shadow-lg transition-all">
                <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Chat History</h3>
                <p class="text-gray-600">
                    Access all your previous conversations. Search and reference past interactions easily.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- How It Works Section --}}
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">How It Works</h2>
            <p class="text-xl text-gray-600">Get started in three simple steps</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-2xl font-bold text-white">1</span>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Sign Up</h3>
                <p class="text-gray-600">Create your account and choose a subscription plan.</p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-2xl font-bold text-white">2</span>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Upload Research</h3>
                <p class="text-gray-600">Upload your PDF research papers to train the AI.</p>
            </div>

            <div class="text-center">
                <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-2xl font-bold text-white">3</span>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Start Chatting</h3>
                <p class="text-gray-600">Your AI assistant is ready to help with medical questions.</p>
            </div>
        </div>
    </div>
</section>

{{-- Pricing Section --}}
<section id="pricing" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Simple Pricing</h2>
            <p class="text-xl text-gray-600">Choose the plan that works for you</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-{{ count($plans) > 0 ? count($plans) : 3 }} gap-8 max-w-5xl mx-auto">
            @forelse($plans as $index => $plan)
                <div class="rounded-2xl border {{ $index === 1 ? 'border-blue-600 shadow-xl scale-105' : 'border-gray-200' }} p-8 relative">
                    @if($index === 1)
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-blue-600 text-white px-4 py-1 rounded-full text-sm font-medium">
                            Popular
                        </div>
                    @endif

                    <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $plan->name }}</h3>
                    <div class="mb-4">
                        <span class="text-4xl font-bold text-gray-900">{{ number_format($plan->price, 0) }}</span>
                        <span class="text-gray-500">EGP / {{ $plan->duration_label }}</span>
                    </div>

                    <p class="text-gray-600 mb-6">{{ $plan->description }}</p>

                    @if($plan->features)
                        <ul class="space-y-3 mb-8">
                            @foreach($plan->features as $feature)
                                <li class="flex items-center text-gray-600">
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <a href="{{ route('register') }}" class="block w-full text-center py-3 rounded-xl font-semibold transition-all {{ $index === 1 ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                        Get Started
                    </a>
                </div>
            @empty
                {{-- Default plans if none in DB --}}
                <div class="rounded-2xl border border-gray-200 p-8">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Basic</h3>
                    <div class="mb-4">
                        <span class="text-4xl font-bold text-gray-900">99</span>
                        <span class="text-gray-500">EGP / month</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            1 PDF Upload
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            100 Chat Messages
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Email Support
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full text-center py-3 rounded-xl font-semibold bg-gray-100 text-gray-900 hover:bg-gray-200 transition-all">
                        Get Started
                    </a>
                </div>

                <div class="rounded-2xl border border-blue-600 shadow-xl p-8 relative scale-105">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-blue-600 text-white px-4 py-1 rounded-full text-sm font-medium">
                        Popular
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Pro</h3>
                    <div class="mb-4">
                        <span class="text-4xl font-bold text-gray-900">199</span>
                        <span class="text-gray-500">EGP / month</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            3 PDF Uploads
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Unlimited Chat
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Priority Support
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full text-center py-3 rounded-xl font-semibold bg-blue-600 text-white hover:bg-blue-700 transition-all">
                        Get Started
                    </a>
                </div>

                <div class="rounded-2xl border border-gray-200 p-8">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Enterprise</h3>
                    <div class="mb-4">
                        <span class="text-4xl font-bold text-gray-900">499</span>
                        <span class="text-gray-500">EGP / month</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Unlimited PDFs
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Unlimited Chat
                        </li>
                        <li class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            24/7 Support
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="block w-full text-center py-3 rounded-xl font-semibold bg-gray-100 text-gray-900 hover:bg-gray-200 transition-all">
                        Get Started
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</section>

{{-- CTA Section --}}
<section class="py-20 bg-gradient-to-r from-blue-600 to-indigo-600">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Ready to Transform Your Practice?</h2>
        <p class="text-xl text-blue-100 mb-8">
            Join hundreds of medical professionals already using DoctorChat.
        </p>
        <a href="{{ route('register') }}" class="bg-white text-blue-600 px-8 py-4 rounded-xl text-lg font-semibold hover:bg-blue-50 transition-all shadow-lg">
            Get Started Now
        </a>
    </div>
</section>
@endsection
