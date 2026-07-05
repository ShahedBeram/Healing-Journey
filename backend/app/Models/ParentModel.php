<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentModel extends Model
{
    // تحديد اسم الجدول صراحةً
    protected $table = 'parents';

    // استخدام user_id كمفتاح أساسي كما في الـ Schema
    protected $primaryKey = 'user_id';

    // إيقاف الترقيم التلقائي لأنه يعتمد على جدول users
    public $incrementing = false;

    protected $fillable = [
        'user_id'
    ];

    /**
     * علاقة الموديل بالحساب الرئيسي (الـ User)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    // علاقة ولي الأمر بأطفاله (يستطيع إدارة أكثر من بروفايل)

    public function children(): HasMany
    {
        return $this->hasMany(ChildProfile::class, 'parent_id', 'user_id');
    }
}
