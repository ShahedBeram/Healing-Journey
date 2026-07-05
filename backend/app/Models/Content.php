<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\AwarenessMotivationalContent;
use Illuminate\Support\Facades\Storage;

class Content extends Model
{
    protected $fillable = ['cover_image', 'title', 'description', 'content_type', 'file_path', 'body', 'status', 'submitted_by', 'decided_by', 'likes_count', 'comments_count', 'approved_at','category_id'];
    protected $casts = ['approved_at' => 'datetime'];

    protected $hidden = [
        'submitted_by',
        'decided_by',
        'updated_at',
    ];

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function motivationalDetails(): HasOne
    {
        return $this->hasOne(AwarenessMotivationalContent::class, 'content_id');
    }
    public function childContentDetails(): HasOne
    {
        return $this->hasOne(ChildContent::class, 'content_id');
    }
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }
    public function getFilePathAttribute($value)
    {
        if ($value) {
            //  إرجاع الرابط الكامل للملف على السيرفر
            return asset(Storage::url($value));
        }
        return null;
    }
    /*  public function getCoverImageAttribute($value)
    {
        // 1. إذا كان المسار موجوداً، نرجع الرابط الكامل
        if ($value) {
            return asset(Storage::url($value));
        }

        // 2. إذا كان فارغاً، نرجع صورة افتراضية أو رابط توليد ديناميكي بناءً على العنوان
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->title ?? 'Content') . '&background=random&size=512';
    }*/
    public function getCoverImageAttribute($value)
    {
        //  إذا كان $value (القيمة الخام من قاعدة البيانات) موجوداً، نرجع الرابط الكامل
        if ($value) {
            return asset('storage/' . $value);
        }

        // إذا كان فارغاً، نرجع الرابط الديناميكي
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->title ?? 'Campaign') . '&background=random&size=512';
    }
    //protected $appends = ['cover_image', 'file_path'];
}
