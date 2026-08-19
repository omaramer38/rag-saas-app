<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\DoctorFile;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RagService
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.rag.base_url', 'http://localhost:5000');
        $this->apiKey = config('services.rag.api_key', '');
        $this->timeout = config('services.rag.timeout', 120);
    }

    /**
     * Upload a document with streaming progress callbacks.
     * Returns the final result with quality metrics.
     */
    public function uploadDocumentStreaming(DoctorFile $file, callable $onProgress = null): array
    {
        // Use Storage facade to resolve the correct path for the 'local' disk
        $filePath = Storage::disk('local')->path($file->file_path);

        if (!file_exists($filePath)) {
            // Fallback: try storage/app/private (Laravel 12 default)
            $filePath = storage_path('app/private/' . $file->file_path);
            if (!file_exists($filePath)) {
                // Fallback: try storage/app
                $filePath = storage_path('app/' . $file->file_path);
            }
        }

        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$file->file_path}");
        }

        try {
            $response = Http::timeout($this->timeout)
                ->attach('file', file_get_contents($filePath), $file->file_name)
                ->withHeaders([
                    'Accept' => 'application/x-ndjson',
                ])
                ->post("{$this->baseUrl}/api/v1/documents/upload", [
                    'user_id' => $file->user_id,
                ]);

            if ($response->failed()) {
                Log::error('RAG upload failed', [
                    'file_id' => $file->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('Failed to upload document to RAG service');
            }

            // Parse NDJSON response line by line
            $lines = explode("\n", trim($response->body()));
            $lastUpdate = [];

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                $data = json_decode($line, true);
                if ($data) {
                    $lastUpdate = $data;
                    if ($onProgress) {
                        $onProgress($data);
                    }
                }
            }

            return $lastUpdate;

        } catch (\Exception $e) {
            Log::error('RAG upload error', [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Upload a document (non-streaming, for backward compatibility).
     */
    public function uploadDocument(DoctorFile $file): array
    {
        return $this->uploadDocumentStreaming($file);
    }

    /**
     * Send a chat message and get AI response.
     */
    public function chat(User $user, ChatSession $session, string $message): array
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders($this->headers())
            ->post("{$this->baseUrl}/api/v1/chat", [
                'user_id' => $user->id,
                'session_id' => (string) $session->id,
                'message' => $message,
            ]);

        if ($response->failed()) {
            Log::error('RAG chat failed', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Failed to get response from chatbot');
        }

        return $response->json();
    }

    /**
     * Delete a document from the RAG service.
     */
    public function deleteDocument(int $userId): bool
    {
        $response = Http::withHeaders($this->headers())
            ->delete("{$this->baseUrl}/api/v1/documents/{$userId}");

        if ($response->failed()) {
            Log::error('RAG delete failed', ['user_id' => $userId]);
            return false;
        }

        return true;
    }

    /**
     * Get user's document info.
     */
    public function getDocumentInfo(int $userId): ?array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders($this->headers())
                ->get("{$this->baseUrl}/api/v1/documents/{$userId}");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('RAG document info failed', ['user_id' => $userId]);
        }

        return null;
    }

    /**
     * Get overall system statistics.
     */
    public function getStats(): ?array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders($this->headers())
                ->get("{$this->baseUrl}/api/v1/stats");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('RAG stats failed');
        }

        return null;
    }

    /**
     * Get user's retrieval quality metrics.
     */
    public function getUserMetrics(int $userId): ?array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders($this->headers())
                ->get("{$this->baseUrl}/api/v1/user/{$userId}/metrics");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('RAG user metrics failed', ['user_id' => $userId]);
        }

        return null;
    }

    /**
     * Check RAG service health.
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/api/v1/health");

            return $response->successful() && $response->json('status') === 'healthy';
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get request headers.
     */
    private function headers(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($this->apiKey) {
            $headers['X-API-Key'] = $this->apiKey;
        }

        return $headers;
    }
}
