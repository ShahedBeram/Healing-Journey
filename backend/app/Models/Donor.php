<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donor extends Model
{
    // بما أن user_id هو المفتاح الأساسي وهو Foreign Key في نفس الوقت
    protected $primaryKey = 'user_id';

    // إيقاف الترقيم التلقائي لأنه يعتمد على جدول users
    public $incrementing = false;

    // الحقول القابلة للتعبئة
    protected $fillable = [
        'user_id'
    ];


    // علاقة الموديل بالحساب الرئيسي
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // علاقة المتبرع بحملات التبرع التي يطلقها
    public function campaigns(): HasMany
    {
        return $this->hasMany(DonationCampaign::class, 'user_id', 'user_id');
    }
}
