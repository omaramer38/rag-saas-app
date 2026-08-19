<?php
/**
 * Background RAG Processor
 * Called by: exec('php rag-process.php {file_id}')
 * Reads file from DB, sends to RAG, updates status.
 */

$fileId = $argv[1] ?? null;
if (!$fileId) {
    fwrite(STDERR, "Usage: php rag-process.php {file_id}\n");
    exit(1);
}

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DoctorFile;
use App\Services\RagService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

$file = DoctorFile::find($fileId);
if (!$file) {
    fwrite(STDERR, "File #{$fileId} not found\n");
    exit(1);
}

Log::info("Background RAG processing started", ['file_id' => $fileId]);

try {
    $ragService = app(RagService::class);

    $result = $ragService->uploadDocumentStreaming($file, function ($progress) use ($fileId) {
        Cache::put("file_progress_{$fileId}", $progress, now()->addMinutes(10));
    });

    $status = $result['step'] === 'completed' ? 'ready' : 'failed';

    $file->update([
        'status' => $status,
        'rag_document_id' => $result['document_id'] ?? null,
        'processed_at' => now(),
    ]);

    // Store final result in cache for frontend to pick up
    Cache::put("file_result_{$fileId}", [
        'success' => true,
        'chunks' => $result['total_chunks'] ?? 0,
        'vectors' => $result['total_vectors'] ?? 0,
        'metrics' => $result['quality_metrics'] ?? [],
        'processing_time_ms' => $result['processing_time_ms'] ?? 0,
    ], now()->addMinutes(10));

    Cache::forget("file_progress_{$fileId}");

    Log::info("Background RAG processing completed", ['file_id' => $fileId, 'status' => $status]);

} catch (\Exception $e) {
    $file->update(['status' => 'failed']);
    Cache::forget("file_progress_{$fileId}");
    Cache::put("file_result_{$fileId}", [
        'success' => false,
        'error' => $e->getMessage(),
    ], now()->addMinutes(10));

    Log::error("Background RAG processing failed", ['file_id' => $fileId, 'error' => $e->getMessage()]);
}
