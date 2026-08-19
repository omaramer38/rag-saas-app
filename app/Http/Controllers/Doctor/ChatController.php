<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $sessions = $user->chatSessions()
            ->with('lastMessage')
            ->latest()
            ->get();

        return view('doctor.chat.index', compact('sessions'));
    }

    public function show(ChatSession $session)
    {
        $user = auth()->user();

        // Ensure user owns this session
        if ($session->user_id !== $user->id) {
            abort(403);
        }

        $session->load('messages');
        $sessions = $user->chatSessions()
            ->with('lastMessage')
            ->latest()
            ->get();

        return view('doctor.chat.show', compact('session', 'sessions'));
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'session_id' => 'nullable|exists:chat_sessions,id',
        ]);

        $user = auth()->user();

        // Create or get session
        if ($request->session_id) {
            $session = ChatSession::where('id', $request->session_id)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            $session = ChatSession::create([
                'user_id' => $user->id,
                'title' => Str::limit(strip_tags($request->message), 50),
            ]);
        }

        // Save user message
        ChatMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => $request->message,
        ]);

        try {
            $startTime = microtime(true);

            // Get AI response from RAG service
            $ragService = app(RagService::class);
            $response = $ragService->chat($user, $session, $request->message);

            $responseTime = round((microtime(true) - $startTime) * 1000);

            $answer = $response['answer'] ?? 'Sorry, I could not process your request.';
            $sources = $response['sources'] ?? [];

            // Save assistant message
            $assistantMessage = ChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => $answer,
                'tokens_used' => $response['tokens_used'] ?? null,
                'response_time_ms' => $responseTime,
                'metadata' => [
                    'sources' => $sources,
                    'chunks_used' => $response['chunks_used'] ?? 0,
                    'total_vectors' => $response['total_vectors'] ?? 0,
                    'retrieval_info' => $response['retrieval_info'] ?? [],
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => $assistantMessage,
                'session_id' => $session->id,
                'sources' => $sources,
                'retrieval_info' => $response['retrieval_info'] ?? [],
            ]);

        } catch (\Exception $e) {
            Log::error('Chat error', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            // Save error message
            $errorMessage = ChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => 'Sorry, I encountered an error processing your request. Please try again.',
            ]);

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'session_id' => $session->id,
                'error' => 'Failed to get response',
            ], 500);
        }
    }

    public function rename(Request $request, ChatSession $session)
    {
        $user = auth()->user();

        if ($session->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $session->update(['title' => $request->title]);

        return response()->json(['success' => true]);
    }

    public function destroy(ChatSession $session)
    {
        $user = auth()->user();

        if ($session->user_id !== $user->id) {
            abort(403);
        }

        $session->delete();

        return response()->json(['success' => true]);
    }
}
