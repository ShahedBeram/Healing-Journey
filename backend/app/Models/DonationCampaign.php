<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationCampaign extends Model
{
    // أضفنا 'type' إلى الـ fillable
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'cover_image',
        'start_date',
        'end_date',
        'status',
        'type',
        'button_text',
        'action_link',
        'contact_info',
        'decided_by',
        'approved_at',
        'likes_count',
        'comments_count',
        'category_id'
    ];

    // إضافة التواريخ كـ Casts لضمان تحويلها لـ Carbon Objects تلقائياً
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'approved_at' => 'datetime'
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }
    public function getCoverImageAttribute($value)
    {
        //  إذا كان $value (القيمة الخام من قاعدة البيانات) موجوداً، نرجع الرابط الكامل
        if ($value) {
            return asset('storage/' . $value);
        }

        // إذا كان فارغاً، نرجع الرابط الديناميكي
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->title ?? 'Campaign') . '&background=random&size=512';
    }


    public function getStatusAttribute($value)
    {
        // إذا كانت مكتملة مخزنياً، لا نحتاج لفحص التاريخ
        if ($value === 'completed') {
            return 'completed';
        }

        // إذا كانت نشطة وتاريخها انتهى، فهي "مكتملة منطقياً"
        if ($value === 'active' && $this->end_date?->isPast()) {
            return 'completed';
        }

        return $value;
    }
}
