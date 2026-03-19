<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;

class DashboardController extends Controller
{
    public function index(ReportService $reportService)
    {
        $stats = $reportService->dashboardStats();

        return view('admin.dashboard', compact('stats'));
    }
}
