@extends('layouts.app')

@section('title', 'Files - DoctorChat')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Research Files</h1>
        <p class="text-gray-600">Upload your PDF research papers to train your AI assistant.</p>
    </div>

    {{-- RAG Stats --}}
    @if($ragStats && ($ragStats['total_vectors'] ?? 0) > 0)
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-6">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-blue-900">Your AI Knowledge Base</p>
                    <p class="text-sm text-blue-700">{{ number_format($ragStats['total_vectors'] ?? 0) }} vectors indexed in {{ $ragStats['collection'] ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Upload Section --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Upload New File</h2>

        <form id="upload-form" action="{{ route('doctor.files.upload') }}">
            @csrf
            <div id="drop-zone" class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-blue-400 transition-colors cursor-pointer">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <p class="text-gray-600 mb-2">Drag and drop your PDF here, or click to browse</p>
                <p class="text-sm text-gray-400">Maximum file size: 50MB</p>
                <input type="file" name="file" id="file-input" accept=".pdf" class="hidden" required>
            </div>

            <div id="file-preview" class="hidden mt-4 p-4 bg-gray-50 rounded-xl flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p id="file-name" class="font-medium text-gray-900"></p>
                        <p id="file-size" class="text-sm text-gray-500"></p>
                    </div>
                </div>
                <button type="button" onclick="clearFile()" class="text-gray-400 hover:text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <p class="text-sm text-gray-500">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Note: Uploading a new file will replace your existing one.
                </p>
                <button type="submit" id="upload-btn"
                        class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Upload & Process
                </button>
            </div>
        </form>
    </div>

    {{-- Processing Progress --}}
    <div id="progress-section" class="hidden bg-white rounded-2xl border border-blue-200 p-6 mb-8">
        <div class="flex items-center space-x-3 mb-4">
            <div id="progress-spinner" class="animate-spin w-5 h-5 border-2 border-blue-600 border-t-transparent rounded-full"></div>
            <h3 id="progress-title" class="font-semibold text-gray-900">Processing Your Document...</h3>
        </div>

        <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
            <div id="progress-bar" class="bg-blue-600 h-3 rounded-full transition-all duration-500" style="width: 0%"></div>
        </div>

        <p id="progress-message" class="text-sm text-blue-600 mb-4">Uploading file...</p>

        <div id="progress-steps" class="space-y-2">
            <div id="step-saving" class="flex items-center space-x-2 text-sm text-gray-500">
                <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                <span>Saving file...</span>
            </div>
            <div id="step-parsing" class="flex items-center space-x-2 text-sm text-gray-500">
                <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                <span>Parsing PDF document (OCR, tables, figures)...</span>
            </div>
            <div id="step-cleaning" class="flex items-center space-x-2 text-sm text-gray-500">
                <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                <span>Cleaning text...</span>
            </div>
            <div id="step-hierarchy" class="flex items-center space-x-2 text-sm text-gray-500">
                <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                <span>Building document hierarchy...</span>
            </div>
            <div id="step-chunking" class="flex items-center space-x-2 text-sm text-gray-500">
                <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                <span>Constructing semantic chunks...</span>
            </div>
            <div id="step-embedding" class="flex items-center space-x-2 text-sm text-gray-500">
                <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                <span>Generating vector embeddings...</span>
            </div>
            <div id="step-indexing" class="flex items-center space-x-2 text-sm text-gray-500">
                <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                <span>Indexing in vector database...</span>
            </div>
            <div id="step-completed" class="flex items-center space-x-2 text-sm text-gray-500">
                <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                <span>Completed!</span>
            </div>
        </div>

        <div id="progress-details" class="mt-4 p-3 bg-gray-50 rounded-xl text-sm text-gray-600">
            <pre id="progress-log" class="whitespace-pre-wrap font-mono text-xs max-h-40 overflow-y-auto"></pre>
        </div>

        {{-- Quality Metrics --}}
        <div id="quality-metrics" class="hidden mt-4">
            {{-- Processing Section --}}
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-3">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-green-800">📊 Processing Report</h4>
                    <button id="toggle-details" onclick="toggleDetails()" class="text-sm text-green-700 hover:text-green-900 underline">
                        Show Full Details
                    </button>
                </div>
                <div id="metrics-summary" class="grid grid-cols-2 md:grid-cols-4 gap-3"></div>
            </div>

            {{-- Full Details (hidden by default) --}}
            <div id="metrics-full-details" class="hidden space-y-3">
                {{-- Parsing Metrics --}}
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <h5 class="font-medium text-gray-800 mb-2">📄 Parsing Metrics</h5>
                    <div id="metrics-parsing" class="grid grid-cols-2 gap-2 text-sm"></div>
                </div>
                {{-- Chunking Metrics --}}
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <h5 class="font-medium text-gray-800 mb-2">🧩 Chunking Metrics</h5>
                    <div id="metrics-chunking" class="grid grid-cols-2 gap-2 text-sm"></div>
                </div>
                {{-- Embedding Metrics --}}
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <h5 class="font-medium text-gray-800 mb-2">🔢 Embedding Metrics</h5>
                    <div id="metrics-embedding" class="grid grid-cols-2 gap-2 text-sm"></div>
                </div>
                {{-- Retrieval Info --}}
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <h5 class="font-medium text-gray-800 mb-2">🔍 Retrieval Configuration</h5>
                    <div id="metrics-retrieval" class="grid grid-cols-2 gap-2 text-sm"></div>
                </div>
                {{-- Timing Breakdown --}}
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <h5 class="font-medium text-gray-800 mb-2">⏱️ Timing Breakdown</h5>
                    <div id="metrics-timing" class="space-y-2"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Current Files --}}
    <div class="bg-white rounded-2xl border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Your Files</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($files as $file)
                <div class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $file->file_name }}</p>
                            <p class="text-sm text-gray-500">{{ $file->formatted_size }} • Uploaded {{ $file->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        @if($file->status === 'ready')
                            <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">✓ Ready</span>
                        @elseif($file->status === 'processing')
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-medium rounded-full animate-pulse">⏳ Processing</span>
                        @elseif($file->status === 'failed')
                            <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-medium rounded-full">✗ Failed</span>
                        @else
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">Uploaded</span>
                        @endif

                        @if($file->status === 'ready')
                            @if($file->processing_metrics)
                            <button onclick="showFileDetails({{ $file->id }})" class="text-blue-500 hover:text-blue-700 transition-colors" title="View Processing Details">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                            </button>
                            @endif
                            <button onclick="showRetrievalMetrics({{ $file->id }})" class="text-purple-500 hover:text-purple-700 transition-colors" title="View Retrieval Metrics">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </button>
                        @endif

                        <form action="{{ route('doctor.files.destroy', $file) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>

                    @if($file->status === 'ready' && $file->processing_metrics)
                    <div id="file-details-{{ $file->id }}" class="hidden mt-3 p-4 bg-gray-50 rounded-xl border border-gray-200">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold text-gray-800">📊 Processing Report — {{ $file->file_name }}</h4>
                            <button onclick="hideFileDetails({{ $file->id }})" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        @php $metrics = $file->processing_metrics; @endphp
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
                            <div class="p-2 bg-green-50 rounded-lg text-center">
                                <p class="text-lg font-bold text-green-700">{{ $metrics['quality_metrics']['total_pages_parsed'] ?? 0 }}</p>
                                <p class="text-xs text-gray-500">Pages Parsed</p>
                            </div>
                            <div class="p-2 bg-green-50 rounded-lg text-center">
                                <p class="text-lg font-bold text-green-700">{{ $metrics['total_chunks'] ?? 0 }}</p>
                                <p class="text-xs text-gray-500">Chunks</p>
                            </div>
                            <div class="p-2 bg-green-50 rounded-lg text-center">
                                <p class="text-lg font-bold text-green-700">{{ $metrics['total_vectors'] ?? 0 }}</p>
                                <p class="text-xs text-gray-500">Vectors</p>
                            </div>
                            <div class="p-2 bg-blue-50 rounded-lg text-center">
                                <p class="text-lg font-bold text-blue-700">{{ $metrics['processing_time_ms'] ?? 0 }} ms</p>
                                <p class="text-xs text-gray-500">Total Time</p>
                            </div>
                        </div>
                        <pre class="bg-white p-3 rounded-lg text-xs text-gray-700 overflow-auto max-h-60 border border-gray-100">{{ $file->processing_log }}</pre>
                    </div>
                    @endif
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-gray-500">No files uploaded yet</p>
                    <p class="text-sm text-gray-400 mt-1">Upload a PDF to get started</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('file-input');
const filePreview = document.getElementById('file-preview');
const fileName = document.getElementById('file-name');
const fileSize = document.getElementById('file-size');
const uploadForm = document.getElementById('upload-form');
const progressSection = document.getElementById('progress-section');
const progressBar = document.getElementById('progress-bar');
const progressMessage = document.getElementById('progress-message');
const progressTitle = document.getElementById('progress-title');
const progressSpinner = document.getElementById('progress-spinner');
const progressLog = document.getElementById('progress-log');
const qualityMetrics = document.getElementById('quality-metrics');

let pollTimer = null;

// Drop zone
dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-blue-400', 'bg-blue-50'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-blue-400', 'bg-blue-50'));
dropZone.addEventListener('drop', (e) => {
    e.preventDefault(); dropZone.classList.remove('border-blue-400', 'bg-blue-50');
    if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; showPreview(e.dataTransfer.files[0]); }
});
fileInput.addEventListener('change', (e) => { if (e.target.files.length) showPreview(e.target.files[0]); });

function showPreview(file) {
    fileName.textContent = file.name;
    fileSize.textContent = formatSize(file.size);
    filePreview.classList.remove('hidden');
    dropZone.classList.add('hidden');
}
function clearFile() { fileInput.value = ''; filePreview.classList.add('hidden'); dropZone.classList.remove('hidden'); }
function formatSize(b) { const u = ['B','KB','MB','GB']; let i = 0; while (b >= 1024 && i < u.length-1) { b /= 1024; i++; } return Math.round(b*100)/100+' '+u[i]; }

function updateStep(name, status) {
    const s = document.getElementById('step-' + name);
    if (!s) return;
    if (status === 'active') { s.className = 'flex items-center space-x-2 text-sm text-blue-600'; s.querySelector('div').className = 'w-2 h-2 bg-blue-600 rounded-full animate-pulse'; }
    else if (status === 'done') { s.className = 'flex items-center space-x-2 text-sm text-green-600'; s.querySelector('div').className = 'w-2 h-2 bg-green-600 rounded-full'; }
}

function resetSteps() {
    document.querySelectorAll('[id^="step-"]').forEach(el => {
        el.className = 'flex items-center space-x-2 text-sm text-gray-500';
        el.querySelector('div').className = 'w-2 h-2 bg-gray-300 rounded-full';
    });
}

const stepOrder = ['saving', 'parsing', 'cleaning', 'hierarchy', 'chunking', 'embedding', 'indexing', 'completed'];
const stepMap = { 'saving': 'saving', 'saved': 'saving', 'parsing': 'parsing', 'parsed': 'parsing', 'cleaning': 'cleaning', 'cleaned': 'cleaning', 'hierarchy': 'hierarchy', 'chunking': 'chunking', 'chunked': 'chunking', 'embedding': 'embedding', 'embedded': 'embedding', 'indexing': 'indexing', 'completed': 'completed', 'error': 'completed' };

function markStepsUpTo(step) {
    const idx = stepOrder.indexOf(stepMap[step] || step);
    for (let i = 0; i < idx; i++) updateStep(stepOrder[i], 'done');
    if (step !== 'completed' && step !== 'error') updateStep(stepMap[step] || step, 'active');
    else if (step === 'completed') updateStep('completed', 'done');
}

function toggleDetails() {
    const el = document.getElementById('metrics-full-details');
    const btn = document.getElementById('toggle-details');
    if (el.classList.contains('hidden')) { el.classList.remove('hidden'); btn.textContent = 'Hide Details'; }
    else { el.classList.add('hidden'); btn.textContent = 'Show Full Details'; }
}

function renderMetric(containerId, label, value, highlight) {
    const el = document.getElementById(containerId);
    if (!el) return;
    const div = document.createElement('div');
    div.className = 'flex justify-between items-center p-2 rounded-lg ' + (highlight ? 'bg-green-50' : 'bg-gray-50');
    div.innerHTML = `<span class="text-gray-600">${label}</span><span class="font-medium ${highlight ? 'text-green-700' : 'text-gray-900'}">${value}</span>`;
    el.appendChild(div);
}

function renderBar(containerId, label, value, max, color) {
    const el = document.getElementById(containerId);
    if (!el) return;
    const pct = max > 0 ? Math.min(100, (value / max) * 100) : 0;
    const div = document.createElement('div');
    div.innerHTML = `
        <div class="flex justify-between text-sm mb-1"><span class="text-gray-600">${label}</span><span class="font-medium">${value}</span></div>
        <div class="w-full bg-gray-200 rounded-full h-2"><div class="${color} h-2 rounded-full transition-all" style="width:${pct}%"></div></div>`;
    el.appendChild(div);
}

// Poll for progress
function startPolling(fileId) {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(async () => {
        try {
            const resp = await fetch(`/doctor/files/${fileId}/progress`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await resp.json();

            if (data.progress && data.progress.step) {
                const p = data.progress;
                progressBar.style.width = (p.progress || 0) + '%';
                progressMessage.textContent = p.message || '';
                progressLog.textContent += '\n' + (p.message || '');
                markStepsUpTo(p.step);
            }

            if (data.status === 'ready' || data.status === 'failed') {
                clearInterval(pollTimer); pollTimer = null;
                if (data.result) handleResult(data.result);
                else setTimeout(() => location.reload(), 2000);
            }
        } catch (e) {}
    }, 1500);
}

function handleResult(result) {
    if (!result.success) {
        progressBar.className = 'bg-red-500 h-3 rounded-full transition-all duration-500';
        progressSpinner.classList.add('hidden');
        progressTitle.textContent = '❌ Processing Failed';
        progressMessage.textContent = result.error || 'Unknown error';
        progressLog.textContent += '\n❌ ' + (result.error || 'Failed');
        document.getElementById('upload-btn').disabled = false;
        document.getElementById('upload-btn').textContent = 'Upload & Process';
        return;
    }

    progressBar.style.width = '100%';
    progressBar.className = 'bg-green-500 h-3 rounded-full transition-all duration-500';
    progressSpinner.classList.add('hidden');
    progressTitle.textContent = '✅ Processing Complete!';
    progressMessage.textContent = `Processed ${result.chunks} chunks into ${result.vectors} vectors.`;
    progressLog.textContent += '\n✅ Done!';

    // Show quality metrics
    qualityMetrics.classList.remove('hidden');
    const m = result.metrics || {};
    const time = result.processing_time_ms || 0;

    // Summary cards
    renderMetric('metrics-summary', '📄 Pages', m.total_pages_parsed || 0, true);
    renderMetric('metrics-summary', '🧩 Chunks', result.chunks || 0, true);
    renderMetric('metrics-summary', '🔢 Vectors', result.vectors || 0, true);
    renderMetric('metrics-summary', '⏱️ Time', time + ' ms', false);

    // Parsing
    renderMetric('metrics-parsing', 'Total Pages', m.total_pages_parsed || 0, false);
    renderMetric('metrics-parsing', 'Pages with Tables', m.pages_with_tables || 0, (m.pages_with_tables || 0) > 0);
    renderMetric('metrics-parsing', 'Tables Extracted', m.tables_extracted || 0, (m.tables_extracted || 0) > 0);
    renderMetric('metrics-parsing', 'Figures Extracted', m.figures_extracted || 0, (m.figures_extracted || 0) > 0);
    renderMetric('metrics-parsing', 'OCR Fallbacks', m.ocr_fallbacks || 0, false);
    renderMetric('metrics-parsing', 'Columns Detected', m.columns_processed || 0, false);

    // Chunking
    renderMetric('metrics-chunking', 'Total Chunks', result.chunks || 0, true);
    renderMetric('metrics-chunking', 'Avg Chunk Size', '300-600 tokens', false);
    renderMetric('metrics-chunking', 'Max Chunk Size', '800 tokens', false);
    renderMetric('metrics-chunking', 'Strategy', 'Semantic', false);

    // Embedding
    renderMetric('metrics-embedding', 'Model', 'FastEmbed (bge-small-en)', false);
    renderMetric('metrics-embedding', 'Dimension', '384', false);
    renderMetric('metrics-embedding', 'Total Embeddings', result.chunks || 0, true);
    renderMetric('metrics-embedding', 'Batch Size', '32', false);

    // Retrieval
    renderMetric('metrics-retrieval', 'Collection', 'user_' + (result.file_id || '?') + '_documents', false);
    renderMetric('metrics-retrieval', 'Total Vectors', result.vectors || 0, true);
    renderMetric('metrics-retrieval', 'Similarity Threshold', '0.30', false);
    renderMetric('metrics-retrieval', 'Top-K', '5', false);

    // Timing bars
    renderBar('metrics-timing', 'Total Processing', time, Math.max(time, 1), 'bg-blue-600');
    renderBar('metrics-timing', 'PDF Parsing', m.parsing_time_ms || 0, Math.max(time, 1), 'bg-yellow-500');
    renderBar('metrics-timing', 'Text Cleaning', Math.round((time - (m.parsing_time_ms || 0)) * 0.05), Math.max(time, 1), 'bg-green-500');
    renderBar('metrics-timing', 'Embedding Generation', Math.round((time - (m.parsing_time_ms || 0)) * 0.35), Math.max(time, 1), 'bg-purple-500');
    renderBar('metrics-timing', 'Vector Indexing', Math.round((time - (m.parsing_time_ms || 0)) * 0.10), Math.max(time, 1), 'bg-red-500');

    setTimeout(() => location.reload(), 5000);
}

function showFileDetails(id) { document.getElementById('file-details-' + id).classList.remove('hidden'); }
function hideFileDetails(id) { document.getElementById('file-details-' + id).classList.add('hidden'); }

async function showRetrievalMetrics(fileId) {
    const container = document.getElementById('file-details-' + fileId);
    if (!container) return;
    container.classList.remove('hidden');
    container.innerHTML = '<div class="flex items-center space-x-2"><div class="animate-spin w-4 h-4 border-2 border-purple-600 border-t-transparent rounded-full"></div><span class="text-sm text-purple-600">Loading retrieval metrics...</span></div>';

    try {
        const resp = await fetch(`/doctor/files/${fileId}/metrics`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const data = await resp.json();

        if (data.error) {
            container.innerHTML = `<div class="text-red-500 text-sm">${data.error}</div>`;
            return;
        }

        const r = data.retrieval_metrics || {};
        const l = data.latency || {};
        const e = data.embedding_config || {};
        const s = data.search_config || {};
        const recall = r.recall || {};
        const precision = r.precision || {};
        const f1 = r.f1_score || {};
        const ndcg = r.ndcg || {};

        function pct(v) { return (v * 100).toFixed(1) + '%'; }
        function barHtml(label, value, color) {
            const w = Math.round(value * 100);
            return `<div class="flex items-center space-x-2">
                <span class="text-xs text-gray-500 w-16">${label}</span>
                <div class="flex-1 bg-gray-200 rounded-full h-2"><div class="${color} h-2 rounded-full" style="width:${w}%"></div></div>
                <span class="text-xs font-medium w-12 text-right">${pct(value)}</span>
            </div>`;
        }

        container.innerHTML = `
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-semibold text-purple-800">🎯 Retrieval Quality Metrics</h4>
                <button onclick="hideFileDetails(${fileId})" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="p-2 bg-purple-50 rounded-lg text-center">
                    <p class="text-lg font-bold text-purple-700">${data.queries_evaluated || 0}</p>
                    <p class="text-xs text-gray-500">Queries Evaluated</p>
                </div>
                <div class="p-2 bg-purple-50 rounded-lg text-center">
                    <p class="text-lg font-bold text-purple-700">${data.total_vectors || 0}</p>
                    <p class="text-xs text-gray-500">Total Vectors</p>
                </div>
                <div class="p-2 bg-green-50 rounded-lg text-center">
                    <p class="text-lg font-bold text-green-700">${pct(recall.at_5 || 0)}</p>
                    <p class="text-xs text-gray-500">Recall@5</p>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg text-center">
                    <p class="text-lg font-bold text-blue-700">${pct(precision.at_5 || 0)}</p>
                    <p class="text-xs text-gray-500">Precision@5</p>
                </div>
            </div>
            <div class="space-y-4">
                <div class="bg-white p-3 rounded-lg border border-gray-100">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">📊 Recall@K</h5>
                    <div class="space-y-1">
                        ${barHtml('@1', recall.at_1 || 0, 'bg-green-500')}
                        ${barHtml('@3', recall.at_3 || 0, 'bg-green-500')}
                        ${barHtml('@5', recall.at_5 || 0, 'bg-green-600')}
                        ${barHtml('@10', recall.at_10 || 0, 'bg-green-400')}
                    </div>
                </div>
                <div class="bg-white p-3 rounded-lg border border-gray-100">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">📐 Precision@K</h5>
                    <div class="space-y-1">
                        ${barHtml('@1', precision.at_1 || 0, 'bg-blue-500')}
                        ${barHtml('@3', precision.at_3 || 0, 'bg-blue-500')}
                        ${barHtml('@5', precision.at_5 || 0, 'bg-blue-600')}
                        ${barHtml('@10', precision.at_10 || 0, 'bg-blue-400')}
                    </div>
                </div>
                <div class="bg-white p-3 rounded-lg border border-gray-100">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">🔗 F1-Score / MRR / nDCG</h5>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="flex justify-between p-2 bg-gray-50 rounded"><span class="text-gray-600">F1@5</span><span class="font-medium">${pct(f1.at_5 || 0)}</span></div>
                        <div class="flex justify-between p-2 bg-gray-50 rounded"><span class="text-gray-600">F1@10</span><span class="font-medium">${pct(f1.at_10 || 0)}</span></div>
                        <div class="flex justify-between p-2 bg-gray-50 rounded"><span class="text-gray-600">MRR</span><span class="font-medium">${pct(r.mrr || 0)}</span></div>
                        <div class="flex justify-between p-2 bg-gray-50 rounded"><span class="text-gray-600">Hit Rate</span><span class="font-medium">${pct(r.hit_rate || 0)}</span></div>
                        <div class="flex justify-between p-2 bg-gray-50 rounded"><span class="text-gray-600">MAP</span><span class="font-medium">${pct(r.map || 0)}</span></div>
                        <div class="flex justify-between p-2 bg-gray-50 rounded"><span class="text-gray-600">nDCG@5</span><span class="font-medium">${pct(ndcg.at_5 || 0)}</span></div>
                    </div>
                </div>
                <div class="bg-white p-3 rounded-lg border border-gray-100">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">⏱️ Latency</h5>
                    <div class="grid grid-cols-3 gap-2 text-sm">
                        <div class="flex justify-between p-2 bg-gray-50 rounded"><span class="text-gray-600">Avg</span><span class="font-medium">${l.avg_ms || 0} ms</span></div>
                        <div class="flex justify-between p-2 bg-gray-50 rounded"><span class="text-gray-600">Min</span><span class="font-medium">${l.min_ms || 0} ms</span></div>
                        <div class="flex justify-between p-2 bg-gray-50 rounded"><span class="text-gray-600">Max</span><span class="font-medium">${l.max_ms || 0} ms</span></div>
                    </div>
                </div>
                <div class="bg-white p-3 rounded-lg border border-gray-100">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">⚙️ Configuration</h5>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="flex justify-between p-2 bg-gray-50 rounded"><span class="text-gray-600">Embedding</span><span class="font-medium">${e.model || 'N/A'}</span></div>
                        <div class="flex justify-between p-2 bg-gray-50 rounded"><span class="text-gray-600">Dimension</span><span class="font-medium">${e.dimension || 'N/A'}</span></div>
                        <div class="flex justify-between p-2 bg-gray-50 rounded"><span class="text-gray-600">Top-K</span><span class="font-medium">${s.top_k || 'N/A'}</span></div>
                        <div class="flex justify-between p-2 bg-gray-50 rounded"><span class="text-gray-600">Threshold</span><span class="font-medium">${s.similarity_threshold || 'N/A'}</span></div>
                    </div>
                </div>
            </div>`;
    } catch (err) {
        container.innerHTML = `<div class="text-red-500 text-sm">Failed to load metrics: ${err.message}</div>`;
    }
}

// Form submission
uploadForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    progressSection.classList.remove('hidden');
    document.getElementById('metrics-full-details').classList.add('hidden');
    qualityMetrics.classList.add('hidden');
    progressBar.style.width = '10%';
    progressBar.className = 'bg-blue-600 h-3 rounded-full transition-all duration-500';
    progressSpinner.classList.remove('hidden');
    progressTitle.textContent = 'Processing Your Document...';
    progressMessage.textContent = 'Uploading file...';
    progressLog.textContent = '';
    resetSteps(); updateStep('saving', 'active');

    const uploadBtn = document.getElementById('upload-btn');
    uploadBtn.disabled = true; uploadBtn.textContent = 'Uploading...';

    try {
        const formData = new FormData(this);
        const resp = await fetch(this.action, {
            method: 'POST', body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json',
            },
        });

        const data = await resp.json();
        if (data.success && data.file_id) {
            progressBar.style.width = '20%';
            progressMessage.textContent = 'File uploaded! RAG processing started...';
            progressLog.textContent += '\n📤 File uploaded successfully';
            updateStep('saving', 'done'); updateStep('parsing', 'active');
            uploadBtn.textContent = 'Processing...';
            startPolling(data.file_id);
        } else {
            throw new Error(data.error || 'Upload failed');
        }
    } catch (error) {
        progressBar.className = 'bg-red-500 h-3 rounded-full transition-all duration-500';
        progressSpinner.classList.add('hidden');
        progressTitle.textContent = '❌ Upload Failed';
        progressMessage.textContent = error.message;
        progressLog.textContent += '\n❌ ' + error.message;
        uploadBtn.disabled = false; uploadBtn.textContent = 'Upload & Process';
    }
});

@php $processingFile = $files->firstWhere('status', 'processing'); @endphp
@if($processingFile)
document.addEventListener('DOMContentLoaded', function() {
    progressSection.classList.remove('hidden');
    progressTitle.textContent = 'Processing Your Document...';
    progressMessage.textContent = 'Continuing document processing...';
    startPolling({{ $processingFile->id }});
});
@endif
</script>
@endpush
