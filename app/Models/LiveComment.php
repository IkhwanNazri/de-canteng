<?php

namespace App\Models;

use Database\Factories\LiveCommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveComment extends Model
{
    /** @use HasFactory<LiveCommentFactory> */
    use HasFactory;

    protected $fillable = ['live_session_id', 'user_id', 'body', 'is_auto_reply'];

    protected function casts(): array
    {
        return ['is_auto_reply' => 'boolean'];
    }

    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
