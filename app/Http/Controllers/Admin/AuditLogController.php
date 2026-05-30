<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemAuditLog;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = SystemAuditLog::with('user')->latest()->paginate(50);

        return view('admin.audit-logs.index', compact('logs'));
    }
}
