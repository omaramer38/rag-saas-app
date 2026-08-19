<?php

namespace App\Http\Controllers\Admin;

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

        return view('admin.chat.index', compact('sessions'));
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

            $ragService = app(RagService::class);
            $response = $ragService->chat($user, $session, $request->message);

            $responseTime = round((microtime(true) - $startTime) * 1000);

            $assistantMessage = ChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => $response['reply'] ?? 'Sorry, I could not process your request.',
                'tokens_used' => $response['tokens_used'] ?? null,
                'response_time_ms' => $responseTime,
                'metadata' => $response['sources'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => $assistantMessage,
                'session_id' => $session->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Admin chat error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $errorMessage = ChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => 'Sorry, I encountered an error. Please try again.',
            ]);

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'session_id' => $session->id,
            ], 500);
        }
    }
}
