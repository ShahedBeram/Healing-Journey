<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    protected $fillable = ['user_id', 'comment_text', 'commentable_id', 'commentable_type'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted()
    {
        static::created(function ($comment) {
            $comment->commentable?->increment('comments_count');
        });
        static::deleted(function ($comment) {
            $comment->commentable?->decrement('comments_count');
        });
    }
}
