<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChildProfile extends Model
{
    protected $fillable = [
        'parent_id',
        'child_name',
        'age',
        'gender',
        'health_status',
        'illness_story',
        'profile_picture',
    ];

    // إضافة الحقول المحسوبة لتظهر في الـ JSON تلقائياً
    protected $appends = ['total_points', 'stars'];

    // 1. حساب إجمالي النقاط من جدول المحتوى لحظياً
    public function getTotalPointsAttribute()
    {
        return $this->childContents()->sum('points_awarded');
    }

    // 2. حساب النجوم بناءً على إجمالي النقاط المحسوب
    public function getStarsAttribute()
    {
        return floor($this->total_points / 5);
    }

    // علاقة الطفل بولي أمره
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    // علاقة الطفل بالمحتوى الخاص به
    public function childContents(): HasMany
    {
        return $this->hasMany(ChildContent::class, 'child_profile_id', 'id');
    }

    // تنسيق الصورة الشخصية
    public function getProfilePictureAttribute($value)
    {
        if ($value) {
            return asset('storage/' . $value);
        }
        return 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($this->child_name);
    }
}
