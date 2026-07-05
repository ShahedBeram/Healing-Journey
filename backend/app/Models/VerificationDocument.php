<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationDocument extends Model
{
    protected $fillable = [
        'user_id',
        'identity_card',
        'certificate',
        'status',
    ];

    /**
     * المستخدم الذي قام برفع هذه الوثائق
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getIdentityCardAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function getCertificateAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }
}
