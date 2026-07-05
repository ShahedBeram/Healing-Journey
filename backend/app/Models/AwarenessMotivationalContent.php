<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AwarenessMotivationalContent extends Model
{
    protected $table = 'awareness_motivational_content';
    protected $primaryKey = 'content_id';
    public $incrementing = false;

    protected $fillable = [
        'content_id',
        'content_category_type'
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }
}
