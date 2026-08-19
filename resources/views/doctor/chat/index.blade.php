@extends('layouts.app')

@section('title', 'Chat - DoctorChat')

@section('styles')
<style>
    .chat-container { height: calc(100vh - 64px); }
    .messages-container { height: calc(100vh - 180px); }
    .typing-indicator span { animation: blink 1.4s infinite both; }
    .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes blink {
        0%, 80%, 100% { opacity: 0; }
        40% { opacity: 1; }
    }
</style>
@endsection

@section('content')
<div class="flex h-[calc(100vh-64px)]">
    {{-- Sidebar: Chat History --}}
    <div class="w-80 bg-white border-r border-gray-100 flex flex-col">
        <div class="p-4 border-b border-gray-100">
            <a href="{{ route('doctor.chat.index') }}" class="flex items-center justify-center w-full px-4 py-2.5 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Chat
            </a>
        </div>

        <div class="flex-1 overflow-y-auto">
            @forelse($sessions as $session)
                <a href="{{ route('doctor.chat.show', $session) }}"
                   class="block px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors {{ request()->routeIs('doctor.chat.show', $session->id) ? 'bg-blue-50 border-l-4 border-l-blue-600' : '' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $session->title }}</p>
                            @if($session->lastMessage)
                                <p class="text-xs text-gray-500 truncate mt-0.5">{{ $session->lastMessage->content }}</p>
                            @endif
                        </div>
                        <button onclick="event.preventDefault(); deleteSession({{ $session->id }})"
                                class="ml-2 text-gray-400 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </a>
            @empty
                <div class="px-4 py-8 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    <p class="text-sm">No chats yet</p>
                    <p class="text-xs text-gray-400 mt-1">Start a new conversation</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Main Chat Area --}}
    <div class="flex-1 flex flex-col bg-gray-50">
        {{-- Chat Header --}}
        <div class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-900">DoctorChat AI</h2>
                <p class="text-sm text-gray-500">Ask me anything about your research</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-sm text-gray-500">Online</span>
            </div>
        </div>

        {{-- Messages --}}
        <div id="messages" class="flex-1 overflow-y-auto p-6 space-y-4">
            {{-- Welcome Message --}}
            <div class="flex items-start space-x-3 max-w-3xl">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div class="bg-white rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm border border-gray-100">
                    <p class="text-gray-700">Hello! I'm your AI medical assistant. How can I help you today?</p>
                </div>
            </div>
        </div>

        {{-- Typing Indicator --}}
        <div id="typing" class="hidden px-6 pb-2">
            <div class="flex items-start space-x-3 max-w-3xl">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div class="bg-white rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm border border-gray-100">
                    <div class="typing-indicator flex space-x-1">
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input Area --}}
        <div class="bg-white border-t border-gray-100 p-4">
            <form id="chat-form" class="flex items-end space-x-4 max-w-4xl mx-auto">
                @csrf
                <div class="flex-1 relative">
                    <textarea id="message-input"
                              rows="1"
                              placeholder="Type your message..."
                              class="w-full px-4 py-3 pr-12 border border-gray-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                              style="max-height: 120px;"></textarea>
                </div>
                <button type="submit" id="send-btn"
                        class="px-4 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('chat-form');
    const input = document.getElementById('message-input');
    const messages = document.getElementById('messages');
    const typing = document.getElementById('typing');
    const sendBtn = document.getElementById('send-btn');
    let sessionId = null;

    // Auto-resize textarea
    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Send message on Enter (Shift+Enter for new line)
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit'));
        }
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const message = input.value.trim();
        if (!message) return;

        // Add user message
        appendMessage('user', message);
        input.value = '';
        input.style.height = 'auto';

        // Show typing indicator
        typing.classList.remove('hidden');
        messages.scrollTop = messages.scrollHeight;

        // Disable input
        sendBtn.disabled = true;
        input.disabled = true;

        try {
            const response = await fetch('{{ route("doctor.chat.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    session_id: sessionId,
                }),
            });

            const data = await response.json();

            if (data.success) {
                sessionId = data.session_id;
                appendMessage('assistant', data.message.content);
            } else {
                appendMessage('assistant', 'Sorry, something went wrong. Please try again.');
            }
        } catch (error) {
            appendMessage('assistant', 'Network error. Please check your connection and try again.');
        } finally {
            typing.classList.add('hidden');
            sendBtn.disabled = false;
            input.disabled = false;
            input.focus();
        }
    });

    function appendMessage(role, content) {
        const isUser = role === 'user';
        const html = `
            <div class="flex items-start space-x-3 max-w-3xl ${isUser ? 'ml-auto flex-row-reverse' : ''}">
                <div class="w-8 h-8 ${isUser ? 'bg-gray-200' : 'bg-blue-100'} rounded-full flex items-center justify-center flex-shrink-0">
                    ${isUser
                        ? '<span class="text-gray-600 font-semibold">{{ substr(auth()->user()->name, 0, 1) }}</span>'
                        : '<svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>'
                    }
                </div>
                <div class="${isUser ? 'bg-blue-600 text-white rounded-2xl rounded-tr-sm' : 'bg-white rounded-2xl rounded-tl-sm shadow-sm border border-gray-100'} px-4 py-3 max-w-2xl">
                    <p class="${isUser ? 'text-white' : 'text-gray-700'} whitespace-pre-wrap">${escapeHtml(content)}</p>
                </div>
            </div>
        `;
        messages.insertAdjacentHTML('beforeend', html);
        messages.scrollTop = messages.scrollHeight;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});

function deleteSession(id) {
    if (confirm('Are you sure you want to delete this chat?')) {
        fetch(`/doctor/chat/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        }).then(() => {
            window.location.href = '{{ route("doctor.chat.index") }}';
        });
    }
}
</script>
@endpush
