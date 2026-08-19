@extends('layouts.admin')

@section('title', 'Admin Chat')
@section('header', 'Chat')

@section('styles')
<style>
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
<div class="flex h-[calc(100vh-140px)]">
    {{-- Sidebar --}}
    <div class="w-80 bg-white rounded-2xl border border-gray-100 flex flex-col mr-6">
        <div class="p-4 border-b border-gray-100">
            <button onclick="startNewChat()" class="flex items-center justify-center w-full px-4 py-2.5 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Chat
            </button>
        </div>
        <div class="flex-1 overflow-y-auto">
            @foreach($sessions as $session)
                <button onclick="loadSession({{ $session->id }})" class="w-full text-left px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors" data-session="{{ $session->id }}">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $session->title }}</p>
                    @if($session->lastMessage)
                        <p class="text-xs text-gray-500 truncate mt-0.5">{{ $session->lastMessage->content }}</p>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    {{-- Chat Area --}}
    <div class="flex-1 bg-white rounded-2xl border border-gray-100 flex flex-col">
        <div id="messages" class="flex-1 overflow-y-auto p-6 space-y-4">
            <div class="flex items-start space-x-3 max-w-3xl">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div class="bg-gray-100 rounded-2xl rounded-tl-sm px-4 py-3">
                    <p class="text-gray-700">Hello Admin! How can I help you?</p>
                </div>
            </div>
        </div>

        <div id="typing" class="hidden px-6 pb-2">
            <div class="flex items-start space-x-3 max-w-3xl">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div class="bg-gray-100 rounded-2xl rounded-tl-sm px-4 py-3">
                    <div class="typing-indicator flex space-x-1">
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 p-4">
            <form id="chat-form" class="flex items-end space-x-4">
                @csrf
                <textarea id="message-input" rows="1" placeholder="Type your message..."
                          class="flex-1 px-4 py-3 border border-gray-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-blue-500" style="max-height: 120px;"></textarea>
                <button type="submit" id="send-btn" class="px-4 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors">
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
let sessionId = null;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('chat-form');
    const input = document.getElementById('message-input');
    const messages = document.getElementById('messages');
    const typing = document.getElementById('typing');
    const sendBtn = document.getElementById('send-btn');

    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

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

        appendMessage('user', message);
        input.value = '';
        input.style.height = 'auto';
        typing.classList.remove('hidden');
        messages.scrollTop = messages.scrollHeight;
        sendBtn.disabled = true;
        input.disabled = true;

        try {
            const response = await fetch('{{ route("admin.chat.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message, session_id: sessionId }),
            });
            const data = await response.json();
            if (data.success) {
                sessionId = data.session_id;
                appendMessage('assistant', data.message.content);
            } else {
                appendMessage('assistant', 'Sorry, something went wrong.');
            }
        } catch (error) {
            appendMessage('assistant', 'Network error.');
        } finally {
            typing.classList.add('hidden');
            sendBtn.disabled = false;
            input.disabled = false;
            input.focus();
        }
    });
});

function appendMessage(role, content) {
    const messages = document.getElementById('messages');
    const isUser = role === 'user';
    const html = `
        <div class="flex items-start space-x-3 max-w-3xl ${isUser ? 'ml-auto flex-row-reverse' : ''}">
            <div class="w-8 h-8 ${isUser ? 'bg-gray-200' : 'bg-blue-100'} rounded-full flex items-center justify-center flex-shrink-0">
                ${isUser ? '<span class="text-gray-600 font-semibold">A</span>' : '<svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>'}
            </div>
            <div class="${isUser ? 'bg-blue-600 text-white rounded-2xl rounded-tr-sm' : 'bg-gray-100 rounded-2xl rounded-tl-sm'} px-4 py-3 max-w-2xl">
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

function startNewChat() {
    sessionId = null;
    const messages = document.getElementById('messages');
    messages.innerHTML = '';
}

function loadSession(id) {
    sessionId = id;
    window.location.href = '{{ url("/admin/chat") }}?session=' + id;
}
</script>
@endpush
