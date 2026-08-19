@extends('layouts.app')

@section('title', $session->title . ' - DoctorChat')

@section('styles')
<style>
    .chat-container { height: calc(100vh - 64px); }
    .messages-container { height: calc(100vh - 180px); }
    .typing-indicator span { animation: blink 1.4s infinite both; }
    .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes blink { 0%, 80%, 100% { opacity: 0; } 40% { opacity: 1; } }

    .answer-text h1, .answer-text h2, .answer-text h3 { font-weight: 700; margin: 0.5em 0 0.25em; }
    .answer-text h1 { font-size: 1.1em; }
    .answer-text h2 { font-size: 1.05em; }
    .answer-text h3 { font-size: 1em; }
    .answer-text p { margin: 0.4em 0; line-height: 1.6; }
    .answer-text ul, .answer-text ol { margin: 0.4em 0; padding-left: 1.5em; }
    .answer-text li { margin: 0.2em 0; line-height: 1.5; }
    .answer-text strong { font-weight: 600; }
    .answer-text code { background: #f1f5f9; padding: 1px 4px; border-radius: 3px; font-size: 0.9em; }
    .answer-text table { border-collapse: collapse; width: 100%; margin: 0.5em 0; font-size: 0.85em; }
    .answer-text th, .answer-text td { border: 1px solid #e2e8f0; padding: 4px 8px; text-align: left; }
    .answer-text th { background: #f8fafc; font-weight: 600; }

    .source-card { transition: all 0.2s; }
    .source-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .source-card .score-bar { height: 4px; border-radius: 2px; }
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
            @foreach($sessions as $s)
                <a href="{{ route('doctor.chat.show', $s) }}"
                   class="block px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors {{ $s->id === $session->id ? 'bg-blue-50 border-l-4 border-l-blue-600' : '' }}">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $s->title }}</p>
                        @if($s->lastMessage)
                            <p class="text-xs text-gray-500 truncate mt-0.5">{{ $s->lastMessage->content }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Main Chat Area --}}
    <div class="flex-1 flex flex-col bg-gray-50">
        {{-- Chat Header --}}
        <div class="bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-900">{{ $session->title }}</h2>
                <p class="text-sm text-gray-500">{{ $session->messages->count() }} messages</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-sm text-gray-500">Online</span>
            </div>
        </div>

        {{-- Messages --}}
        <div id="messages" class="flex-1 overflow-y-auto p-6 space-y-6">
            @foreach($session->messages as $message)
                @if($message->isUser())
                    {{-- User Message --}}
                    <div class="flex items-start space-x-3 max-w-3xl ml-auto flex-row-reverse">
                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-gray-600 font-semibold">{{ substr(auth()->user()->name, 0, 1) }}</span>
                        </div>
                        <div class="bg-blue-600 text-white rounded-2xl rounded-tr-sm px-4 py-3 max-w-2xl">
                            <p class="whitespace-pre-wrap">{{ $message->content }}</p>
                        </div>
                    </div>
                @else
                    {{-- Assistant Message --}}
                    <div class="flex items-start space-x-3 max-w-4xl">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <div class="flex-1 space-y-3">
                            {{-- Answer Bubble --}}
                            <div class="bg-white rounded-2xl rounded-tl-sm shadow-sm border border-gray-100 px-5 py-4">
                                <div class="answer-text text-gray-700 text-sm leading-relaxed">
                                    @php
                                        $lines = explode("\n\n---\n\n", $message->content);
                                    @endphp
                                    @foreach($lines as $line)
                                        @if(str_starts_with(trim($line), '**[Source'))
                                            @php
                                                $parts = explode("\n\n", $line, 2);
                                                $header = $parts[0] ?? '';
                                                $body = $parts[1] ?? '';
                                            @endphp
                                            <div class="my-3 p-3 bg-blue-50 rounded-lg border border-blue-100">
                                                <p class="text-xs font-semibold text-blue-700 mb-1">{!! nl2br(e($header)) !!}</p>
                                                <p class="text-sm text-gray-700">{{ $body }}</p>
                                            </div>
                                        @else
                                            <p>{{ $line }}</p>
                                        @endif
                                    @endforeach
                                </div>
                                @if($message->response_time_ms)
                                    <div class="flex items-center space-x-2 mt-3 pt-2 border-t border-gray-50">
                                        <span class="text-xs text-gray-400">⏱️ {{ $message->response_time_ms }}ms</span>
                                        @if($message->metadata && isset($message->metadata['chunks_used']))
                                            <span class="text-xs text-gray-400">•</span>
                                            <span class="text-xs text-gray-400">📚 {{ $message->metadata['chunks_used'] }} sources</span>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Source Cards --}}
                            @if($message->metadata && isset($message->metadata['sources']) && count($message->metadata['sources']) > 0)
                                <div class="space-y-2">
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">📎 Sources from document</p>
                                    @foreach($message->metadata['sources'] as $source)
                                        <div class="source-card bg-white rounded-xl border border-gray-100 p-3 cursor-pointer" onclick="toggleSourceContent(this)">
                                            <div class="flex items-center justify-between mb-1">
                                                <div class="flex items-center space-x-2">
                                                    <span class="w-5 h-5 bg-blue-100 text-blue-700 rounded text-xs font-bold flex items-center justify-center">{{ $source['index'] }}</span>
                                                    <span class="text-xs font-medium text-gray-700">
                                                        @if($source['chapter'] && $source['chapter'] !== 'Unknown')
                                                            {{ $source['chapter'] }}
                                                            @if($source['section'])
                                                                › {{ $source['section'] }}
                                                            @endif
                                                        @else
                                                            {{ $source['document_title'] ?? 'Document' }}
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-xs text-gray-400">📖 p.{{ $source['page_start'] }}@if($source['page_start'] !== $source['page_end'])-{{ $source['page_end'] }}@endif</span>
                                                    <span class="text-xs px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded font-medium">{{ round($source['score'] * 100, 1) }}%</span>
                                                </div>
                                            </div>
                                            {{-- Score bar --}}
                                            <div class="w-full bg-gray-100 rounded-full h-1 mb-2">
                                                <div class="bg-blue-500 score-bar" style="width: {{ round($source['score'] * 100) }}%"></div>
                                            </div>
                                            {{-- Preview (collapsed) --}}
                                            <p class="text-xs text-gray-500 line-clamp-2 source-preview">{{ Str::limit(strip_tags($source['content']), 120) }}</p>
                                            {{-- Full content (hidden) --}}
                                            <div class="source-full-content hidden mt-2 p-3 bg-gray-50 rounded-lg text-xs text-gray-600 leading-relaxed max-h-40 overflow-y-auto">
                                                {{ $source['content'] }}
                                            </div>
                                            @if(!empty($source['table_references']))
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    @foreach($source['table_references'] as $table)
                                                        <span class="text-xs px-1.5 py-0.5 bg-yellow-50 text-yellow-700 rounded">📊 {{ $table }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Typing Indicator --}}
        <div id="typing" class="hidden px-6 pb-2">
            <div class="flex items-start space-x-3 max-w-3xl">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
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
                              placeholder="Ask about your document..."
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
function toggleSourceContent(card) {
    const full = card.querySelector('.source-full-content');
    const preview = card.querySelector('.source-preview');
    if (full.classList.contains('hidden')) {
        full.classList.remove('hidden');
        preview.classList.add('hidden');
    } else {
        full.classList.add('hidden');
        preview.classList.remove('hidden');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('chat-form');
    const input = document.getElementById('message-input');
    const messages = document.getElementById('messages');
    const typing = document.getElementById('typing');
    const sendBtn = document.getElementById('send-btn');
    let sessionId = {{ $session->id }};

    messages.scrollTop = messages.scrollHeight;

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

        appendUserMessage(message);
        input.value = '';
        input.style.height = 'auto';
        typing.classList.remove('hidden');
        messages.scrollTop = messages.scrollHeight;
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
                body: JSON.stringify({ message, session_id: sessionId }),
            });

            const data = await response.json();
            if (data.success) {
                sessionId = data.session_id;
                appendAssistantMessage(data.message.content, data.sources || [], data.message.response_time_ms, data.message.metadata);
            } else {
                appendAssistantMessage('Sorry, something went wrong. Please try again.', [], null, null);
            }
        } catch (error) {
            appendAssistantMessage('Network error. Please check your connection and try again.', [], null, null);
        } finally {
            typing.classList.add('hidden');
            sendBtn.disabled = false;
            input.disabled = false;
            input.focus();
        }
    });

    function appendUserMessage(content) {
        const html = `
            <div class="flex items-start space-x-3 max-w-3xl ml-auto flex-row-reverse">
                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-gray-600 font-semibold">{{ substr(auth()->user()->name, 0, 1) }}</span>
                </div>
                <div class="bg-blue-600 text-white rounded-2xl rounded-tr-sm px-4 py-3 max-w-2xl">
                    <p class="whitespace-pre-wrap">${escapeHtml(content)}</p>
                </div>
            </div>`;
        messages.insertAdjacentHTML('beforeend', html);
        messages.scrollTop = messages.scrollHeight;
    }

    function appendAssistantMessage(content, sources, responseTime, metadata) {
        const sourcesHtml = sources.length > 0 ? `
            <div class="space-y-2">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">📎 Sources from document</p>
                ${sources.map((s, i) => {
                    const pageStr = s.page_start !== s.page_end ? `${s.page_start}-${s.page_end}` : s.page_start;
                    const chapterLabel = s.chapter && s.chapter !== 'Unknown'
                        ? `${s.chapter}${s.section ? ' › ' + s.section : ''}`
                        : (s.document_title || 'Document');
                    const scorePct = Math.round(s.score * 100);
                    const tablesHtml = (s.table_references || []).map(t =>
                        `<span class="text-xs px-1.5 py-0.5 bg-yellow-50 text-yellow-700 rounded">📊 ${escapeHtml(t)}</span>`
                    ).join('');
                    return `
                        <div class="source-card bg-white rounded-xl border border-gray-100 p-3 cursor-pointer" onclick="toggleSourceContent(this)">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center space-x-2">
                                    <span class="w-5 h-5 bg-blue-100 text-blue-700 rounded text-xs font-bold flex items-center justify-center">${s.index || (i+1)}</span>
                                    <span class="text-xs font-medium text-gray-700">${escapeHtml(chapterLabel)}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-400">📖 p.${pageStr}</span>
                                    <span class="text-xs px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded font-medium">${scorePct}%</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1 mb-2">
                                <div class="bg-blue-500 score-bar" style="width:${scorePct}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 line-clamp-2 source-preview">${escapeHtml((s.content || '').substring(0, 120))}</p>
                            <div class="source-full-content hidden mt-2 p-3 bg-gray-50 rounded-lg text-xs text-gray-600 leading-relaxed max-h-40 overflow-y-auto">${escapeHtml(s.content || '')}</div>
                            ${tablesHtml ? `<div class="mt-1 flex flex-wrap gap-1">${tablesHtml}</div>` : ''}
                        </div>`;
                }).join('')}
            </div>` : '';

        const timeHtml = responseTime
            ? `<div class="flex items-center space-x-2 mt-3 pt-2 border-t border-gray-50">
                    <span class="text-xs text-gray-400">⏱️ ${responseTime}ms</span>
                    ${sources.length > 0 ? `<span class="text-xs text-gray-400">•</span><span class="text-xs text-gray-400">📚 ${sources.length} sources</span>` : ''}
               </div>`
            : '';

        // Render answer with source formatting
        const answerLines = content.split('\n\n---\n\n');
        const answerHtml = answerLines.map(line => {
            if (line.startsWith('**[Source')) {
                const parts = line.split('\n\n', 2);
                const header = parts[0] || '';
                const body = parts[1] || '';
                return `<div class="my-3 p-3 bg-blue-50 rounded-lg border border-blue-100">
                    <p class="text-xs font-semibold text-blue-700 mb-1">${escapeHtml(header)}</p>
                    <p class="text-sm text-gray-700">${escapeHtml(body)}</p>
                </div>`;
            }
            return `<p>${escapeHtml(line)}</p>`;
        }).join('');

        const html = `
            <div class="flex items-start space-x-3 max-w-4xl">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div class="flex-1 space-y-3">
                    <div class="bg-white rounded-2xl rounded-tl-sm shadow-sm border border-gray-100 px-5 py-4">
                        <div class="answer-text text-gray-700 text-sm leading-relaxed">
                            ${answerHtml}
                        </div>
                        ${timeHtml}
                    </div>
                    ${sourcesHtml}
                </div>
            </div>`;
        messages.insertAdjacentHTML('beforeend', html);
        messages.scrollTop = messages.scrollHeight;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endpush
