<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'role',
        'content',
        'tokens_used',
        'response_time_ms',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'tokens_used' => 'integer',
            'response_time_ms' => 'integer',
            'metadata' => 'array',
        ];
    }

    // ─── Relationships ─────────────────────────────

    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }

    // ─── Helpers ───────────────────────────────────

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}
