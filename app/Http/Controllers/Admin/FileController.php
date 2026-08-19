<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorFile;
use App\Models\ActivityLog;
use App\Services\RagService;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function index(Request $request)
    {
        $query = DoctorFile::with('user');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where('file_name', 'like', "%{$request->search}%");
        }

        $files = $query->latest()->paginate(15);

        return view('admin.files.index', compact('files'));
    }

    public function destroy(DoctorFile $file)
    {
        // Delete from RAG service
        if ($file->rag_document_id) {
            try {
                $ragService = app(RagService::class);
                $ragService->deleteDocument($file->rag_document_id);
            } catch (\Exception $e) {
                Log::error('Failed to delete RAG document', [
                    'document_id' => $file->rag_document_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Delete from storage
        Storage::disk('private')->delete($file->file_path);

        ActivityLog::log('file_deleted', $file);
        $file->delete();
        StatisticsService::clearCache();

        return redirect()->route('admin.files.index')
            ->with('success', 'File deleted successfully.');
    }
}
