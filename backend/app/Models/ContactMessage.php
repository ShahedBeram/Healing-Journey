<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'message',
        'status',
        'reply_text',
        'replied_at'
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    // العلاقة التي تربط الرسالة بالمستخدم (إذا كان مسجلاً)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
