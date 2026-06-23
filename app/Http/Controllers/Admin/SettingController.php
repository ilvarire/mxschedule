<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SystemAuditLog;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings.index', ['settings' => Setting::orderBy('key')->get()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.entry_window_minutes' => 'required|integer|min:0|max:120',
            'settings.delayed_reveal_hours' => 'required|integer|min:0|max:168',
            'settings.pass_grace_minutes' => 'required|integer|min:0|max:120',
            'settings.academic_session' => 'required|string|max:20',
            'settings.current_semester' => 'required|in:first,second',
            'settings.exam_reminder_hours' => ['required', 'string', 'max:50', 'regex:/^\d+(,\d+)*$/'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            $old = Setting::getValue($key);
            Setting::setValue($key, $value);
            SystemAuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'setting.updated',
                'auditable_type' => Setting::class,
                'auditable_id' => Setting::where('key', $key)->value('id'),
                'old_values' => ['value' => $old],
                'new_values' => ['value' => (string) $value],
                'ip_address' => $request->ip(),
            ]);
        }

        return back()->with('success', 'Settings updated.');
    }
}
