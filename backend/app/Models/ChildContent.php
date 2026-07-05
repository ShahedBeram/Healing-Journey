<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildContent extends Model
{
    protected $table = 'child_contents';
    protected $primaryKey = 'content_id';
    public $incrementing = false;

    // 1. إضافة النجوم إلى الـ appends ليظهر الحقل تلقائياً في الـ JSON
    protected $appends = ['stars_count'];

    protected $fillable = [
        'content_id',
        'child_profile_id',
        'points_awarded',
        'content_category_type'
    ];

    // 2. العلاقة مع المحتوى (لجلب البيانات الأساسية: title, description, body, etc)
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    // 3. العلاقة مع الطفل (لجلب الاسم والصورة الشخصية)
    public function childProfile(): BelongsTo
    {
        return $this->belongsTo(ChildProfile::class, 'child_profile_id');
    }

    // 4. الحساب الرياضي للنجوم (كل 5 نقاط = نجمة)
  public function getStarsCountAttribute()
{
    return floor(($this->points_awarded ?? 0) / 5);
}
}
