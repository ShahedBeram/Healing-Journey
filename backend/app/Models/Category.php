<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_active']; 
    protected $casts = ['is_active' => 'boolean'];

    // لفلترة التصنيفات النشطة فقط
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class, 'category_id');
    }

    public function activitySessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class, 'category_id');
    }
    public function donationCampaigns(): HasMany
    {
        return $this->hasMany(DonationCampaign::class, 'category_id');
    }
}
