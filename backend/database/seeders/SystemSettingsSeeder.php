<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'phone', 'value' => '+970 59 123 4567'],
            ['key' => 'support_email', 'value' => 'healingjourney.support@gmail.com'],
            ['key' => 'address', 'value' => 'رام الله - فلسطين'],
            ['key' => 'facebook_url', 'value' => 'https://facebook.com/healingjourney'],
            ['key' => 'instagram_url', 'value' => 'https://instagram.com/healingjourney'],
            ['key' => 'linkedin_url', 'value' => 'https://linkedin.com/company/healingjourney'],
            ['key' => 'footer_text', 'value' => '© ٢٠٢٦ – رحلة شفاء. جميع الحقوق محفوظة'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}