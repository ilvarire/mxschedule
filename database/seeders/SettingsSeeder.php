<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'entry_window_minutes' => '15',
            'delayed_reveal_hours' => '0',
            'qr_signing_key' => bin2hex(random_bytes(32)),
            'academic_session' => '2025/2026',
            'current_semester' => 'second',
            'pass_grace_minutes' => '5',
            'exam_reminder_hours' => '24,1',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
