<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecoveredChild extends Model
{
    // [إعدادات المفتاح الأساسي للربط مع جدول المستخدمين]
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'age',
        'cancer_type',
        'recovery_date',
        'recovery_duration',
        'location',
        'recovery_story',
    ];
    protected $appends = ['nickname'];
    public function getNicknameAttribute()
    {
        // الألقاب هنا مصممة بصيغة (اللقب/ة) لتناسب التصميم
        $nicknames = [
            'بطل/ة التحدي',
            'نجم/ة الإرادة',
            'صانع/ة الأمل',
            'شريك/ة النجاح',
            'محارب/ة الشجاعة'
        ];

        return $nicknames[$this->user_id % count($nicknames)];
    }

    // --- علاقات المتعافي ---

    // [علاقة الموديل بالحساب الرئيسي]
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // [المحتوى التوعوي/التحفيزي الذي نشره المتعافي]
    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'submitted_by', 'user_id');
    }

    // [الدعوات التي استقبلها المتعافي لحضور الجلسات]
    // نربط بـ recovered_child_id في جدول invitations
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'recovered_child_id', 'user_id');
    }
    /*  public function joinedSessions()
    {
        return $this->invitations()
            ->where('status', 'accepted')
            ->with('session');
    }*/
    public function joinedSessions()
    {
        return ActivitySession::whereHas('invitations', function ($q) {
            $q->where('recovered_child_id', $this->user_id)
                ->where('status', 'accepted');
        });
    }
}
