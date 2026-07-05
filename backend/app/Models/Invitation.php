<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    protected $fillable = [
        'session_id',
        'recovered_child_id',
        'status',
        'sent_at',
        'responded_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * الجلسة التي تم توجيه الدعوة لها
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ActivitySession::class, 'session_id');
    }

    /**
     * المتعافي الذي استلم الدعوة
     */
    public function recoveredChild(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recovered_child_id');
    }
}
