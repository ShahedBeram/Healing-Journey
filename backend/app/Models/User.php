<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    // استخدام الـ Traits الافتراضية مع دعم الـ API Tokens
    use HasApiTokens, HasFactory, Notifiable;

    // الحقول القابلة للتعبئة بناءً على الـ Migration الخاص بكِ
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'phone',
        'job_title',
        'profile_picture',
        'user_type',
        'account_status',
        'rejection_reason',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function verificationDocuments(): HasMany
    {
        return $this->hasMany(VerificationDocument::class, 'user_id');
    }

    public function parentProfile(): HasOne
    {
        return $this->hasOne(ParentModel::class, 'user_id');
    }

    public function specialistProfile(): HasOne
    {
        return $this->hasOne(AwarenessSpecialist::class, 'user_id');
    }

    public function recoveredChildProfile(): HasOne
    {
        return $this->hasOne(RecoveredChild::class, 'user_id');
    }

    public function donorProfile(): HasOne
    {
        return $this->hasOne(Donor::class, 'user_id');
    }


    public function decidedContents(): HasMany
    {
        return $this->hasMany(Content::class, 'decided_by');
    }
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'user_id');
    }
    public function getProfilePictureAttribute($value)
    {
        if ($value) {
            return asset('storage/' . $value);
        }
        //   رابط الـ UI Avatars  تمرير اسم المستخدم ديناميكياً
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name) . '&background=random';
    }
}
