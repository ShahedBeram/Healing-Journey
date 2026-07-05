<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ActivitySession extends Model
{
    protected $fillable = [
        'created_by',
        'cover_image',
        'title',
        'description',
        'date_time',
        'type',
        'session_category',
        'target_audience',
        'join_link',
        'form_link',
        'category_id',
        'participation_method',
        'duration',
        'status',
        'decided_by',
        'approved_at',
        'likes_count',
        'comments_count'
    ];

    protected $casts = [
        'date_time' => 'datetime',
        'recovered_child_participation' => 'boolean',
        'approved_at' => 'datetime',
        'duration' => 'integer',
        'likes_count' => 'integer',
        'comments_count' => 'integer'
    ];
    /**
     * الأخصائي الذي أنشأ الجلسة
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * الأدمن الذي وافق أو رفض الجلسة
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * التصنيف الخاص بالجلسة
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * الدعوات المرتبطة بهذه الجلسة
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'session_id');
    }
    /* public function participants()
    {
        return $this->belongsToMany(User::class, 'session_user', 'session_id', 'user_id');
    }*/
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }
    // في موديل ActivitySession
    public function getStatusAttribute($value)
    {
        // 1. إذا كانت الحالة منتهية فعلياً في قاعدة البيانات، لا تحسبي شيئاً
        if ($value === 'completed') return 'completed';

        $now = now();

        // 2. إذا كانت مقبولة والوقت حان، اعرضيها ongoing
        if ($value === 'accepted' && $this->date_time <= $now) {
            return 'ongoing';
        }

        // 3. إذا كانت جارية، تأكدي هل انتهى وقتها فعلياً؟
        if ($value === 'ongoing' && $this->date_time->addMinutes($this->duration)->isPast()) {
            return 'completed';
        }

        // 4. في أي حالة أخرى (pending, rejected)، أعيدي القيمة المخزنة
        return $value;
    }

    public function getCoverImageAttribute($value)
    {

        // 1. إذا كان المسار موجوداً، نرجع الرابط الكامل
        if ($value) {
            return asset(Storage::url($value));
        }

        // 2. إذا كان فارغاً، نرجع رابط الصورة الافتراضية
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->title ?? 'Content') . '&background=random&size=512';
    }
    /**
     * الحصول على رابط الانضمام (يظهر قبل 15 دقيقة فقط)
     */
    public function getJoinLinkAttribute($value)
    {
        // رابط الانضمام يظهر فقط قبل 15 دقيقة من الموعد
        if (now()->addMinutes(15)->greaterThanOrEqualTo($this->date_time)) {
            return $value; // إرجاع الرابط الحقيقي
        }

        return null; // إرجاع null إذا لم يحن الوقت
    }

    /**
     * الحصول على رابط النموذج (يظهر دائماً)
     */
    public function getFormLinkAttribute($value)
    {
        // رابط النموذج يظهر دائماً للمستخدم، لا يحتاج شرط وقت
        return $value;
    }
}
