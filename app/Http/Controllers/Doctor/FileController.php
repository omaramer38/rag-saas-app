<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorFile;
use App\Services\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $files = $user->files()->latest()->get();
        $ragService = app(RagService::class);
        $ragStats = $ragService->getDocumentInfo($user->id);
        return view('doctor.files.index', compact('files', 'ragStats'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:51200',
        ]);

        $user = auth()->user();

        // Delete old file if exists
        $existingFile = $user->files()->latest()->first();
        if ($existingFile) {
            $this->deleteFile($existingFile);
        }

        // Store new file
        $file = $request->file('file');
        $fileName = uniqid('doc_', true) . '.pdf';
        $filePath = $file->storeAs('doctor-files', $fileName, 'local');

        // Create DB record
        $doctorFile = DoctorFile::create([
            'user_id' => $user->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'status' => 'processing',
        ]);

        // Increase PHP timeout — RAG processing takes 3-30 seconds
        set_time_limit(300);
        ignore_user_abort(true);

        try {
            $ragService = app(RagService::class);

            $result = $ragService->uploadDocumentStreaming($doctorFile, function ($progress) use ($doctorFile) {
                cache()->put(
                    "file_progress_{$doctorFile->id}",
                    $progress,
                    now()->addMinutes(10)
                );
            });

            $status = $result['step'] === 'completed' ? 'ready' : 'failed';

            $doctorFile->update([
                'status' => $status,
                'rag_document_id' => $result['document_id'] ?? null,
                'processed_at' => now(),
                'processing_metrics' => [
                    'quality_metrics' => $result['quality_metrics'] ?? [],
                    'total_chunks' => $result['total_chunks'] ?? 0,
                    'total_vectors' => $result['total_vectors'] ?? 0,
                    'processing_time_ms' => $result['processing_time_ms'] ?? 0,
                    'collection' => $result['collection'] ?? '',
                    'file_name' => $result['file_name'] ?? '',
                    'file_size' => $result['file_size'] ?? 0,
                ],
                'processing_log' => $this->buildProcessingLog($result),
            ]);

            // Store final result for frontend polling
            cache()->put("file_result_{$doctorFile->id}", [
                'success' => $status === 'ready',
                'chunks' => $result['total_chunks'] ?? 0,
                'vectors' => $result['total_vectors'] ?? 0,
                'metrics' => $result['quality_metrics'] ?? [],
                'processing_time_ms' => $result['processing_time_ms'] ?? 0,
                'file_id' => $doctorFile->id,
            ], now()->addMinutes(10));

            cache()->forget("file_progress_{$doctorFile->id}");

            // Return JSON for AJAX
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => "File processed! {$result['total_chunks']} chunks indexed.",
                    'file_id' => $doctorFile->id,
                    'status' => $status,
                ]);
            }

            return redirect()->route('doctor.files.index')
                ->with('success', "File processed! {$result['total_chunks']} chunks indexed.");

        } catch (\Exception $e) {
            Log::error('RAG processing failed', [
                'file_id' => $doctorFile->id,
                'error' => $e->getMessage(),
            ]);

            $doctorFile->update(['status' => 'failed']);

            cache()->put("file_result_{$doctorFile->id}", [
                'success' => false,
                'error' => $e->getMessage(),
            ], now()->addMinutes(10));

            cache()->forget("file_progress_{$doctorFile->id}");

            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'error' => 'File processing failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('doctor.files.index')
                ->with('error', 'File upload failed. Please try again.');
        }
    }

    public function progress(DoctorFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) abort(403);

        $progress = cache()->get("file_progress_{$file->id}");
        $result = cache()->get("file_result_{$file->id}");

        return response()->json([
            'status' => $file->fresh()->status,
            'progress' => $progress,
            'result' => $result,
        ]);
    }

    public function metrics(DoctorFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) abort(403);

        $ragService = app(RagService::class);
        $metrics = $ragService->getUserMetrics($file->user_id);

        return response()->json($metrics);
    }

    public function destroy(DoctorFile $file)
    {
        $user = auth()->user();
        if ($file->user_id !== $user->id) abort(403);
        $this->deleteFile($file);
        return redirect()->route('doctor.files.index')->with('success', 'File deleted successfully.');
    }

    private function buildProcessingLog(array $result): string
    {
        $m = $result['quality_metrics'] ?? [];
        $fileName = $result['file_name'] ?? 'N/A';
        $fileSize = number_format(($result['file_size'] ?? 0) / 1024, 1);
        $pages = $m['total_pages_parsed'] ?? 0;
        $tables = $m['tables_extracted'] ?? 0;
        $figures = $m['figures_extracted'] ?? 0;
        $ocr = $m['ocr_fallbacks'] ?? 0;
        $columns = $m['columns_processed'] ?? 0;
        $chunks = $result['total_chunks'] ?? 0;
        $collection = $result['collection'] ?? 'N/A';
        $vectors = $result['total_vectors'] ?? 0;
        $time = $result['processing_time_ms'] ?? 0;
        $parseTime = $m['parsing_time_ms'] ?? 0;

        $lines = [
            '=== Processing Report ===',
            'File: ' . $fileName,
            'Size: ' . $fileSize . ' KB',
            '',
            '--- Parsing ---',
            'Pages Parsed: ' . $pages,
            'Tables Extracted: ' . $tables,
            'Figures Extracted: ' . $figures,
            'OCR Fallbacks: ' . $ocr,
            'Columns Detected: ' . $columns,
            '',
            '--- Chunking ---',
            'Total Chunks: ' . $chunks,
            'Strategy: Semantic',
            'Target Size: 300-600 tokens',
            'Max Size: 800 tokens',
            '',
            '--- Embedding ---',
            'Model: FastEmbed (bge-small-en)',
            'Dimension: 384',
            'Total Embeddings: ' . $chunks,
            '',
            '--- Vector Store ---',
            'Collection: ' . $collection,
            'Total Vectors: ' . $vectors,
            '',
            '--- Timing ---',
            'Total: ' . $time . ' ms',
            'Parsing: ' . $parseTime . ' ms',
        ];

        return implode("\n", $lines);
    }

    private function deleteFile(DoctorFile $file): void
    {
        try {
            $ragService = app(RagService::class);
            $ragService->deleteDocument($file->user_id);
        } catch (\Exception $e) {
            Log::error('Failed to delete RAG document', ['user_id' => $file->user_id, 'error' => $e->getMessage()]);
        }
        Storage::disk('local')->delete($file->file_path);
        $file->delete();
    }
}
