<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AwarenessSpecialist extends Model
{
    // [إعدادات المفتاح الأساسي للربط مع جدول المستخدمين]
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'specialty',
        'bio'
    ];

    // --- علاقات الأخصائي ---

    // [علاقة الموديل بالحساب الرئيسي]
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // [المحتوى التوعوي والتحفيزي الذي قام الأخصائي برفعه]
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'submitted_by', 'user_id');
    }

    // [الجلسات والأنشطة التي قام الأخصائي بإنشائها]
    public function sessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class, 'created_by', 'user_id');
    }
}
