<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function show(string $type, Request $request, ReportService $service)
    {
        $data = $this->getReportData($type, $request, $service);

        return view("admin.reports.{$type}", $data);
    }

    public function download(string $type, Request $request, ReportService $service)
    {
        $data = $this->getReportData($type, $request, $service);

        if ($request->query('format') === 'csv') {
            return $this->downloadCsv($type, $data);
        }

        $pdf = Pdf::loadView("admin.reports.{$type}", $data);
        return $pdf->download("report-{$type}-" . now()->format('Y-m-d') . '.pdf');
    }

    protected function getReportData(string $type, Request $request, ReportService $service): array
    {
        return match ($type) {
            'attendance' => $request->query('exam_id')
                ? $service->attendanceReport(Exam::findOrFail($request->query('exam_id')))
                : [],
            'system-usage' => ['usage' => $service->systemUsageReport()],
            'load-distribution' => $request->query('exam_id')
                ? ['distribution' => $service->loadDistributionReport(Exam::findOrFail($request->query('exam_id')))]
                : [],
            'missed-exams' => $request->query('exam_id')
                ? ['missed' => $service->missedExamsReport(Exam::findOrFail($request->query('exam_id')))]
                : [],
            default => abort(404, 'Unknown report type.'),
        };
    }

    protected function downloadCsv(string $type, array $data)
    {
        $filename = "report-{$type}-" . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            // Write headers and data based on report type
            if (isset($data['sessions'])) {
                fputcsv($handle, ['Session', 'Student', 'Matric', 'Hall', 'System', 'Status']);
                foreach ($data['sessions'] as $session) {
                    foreach ($session->allocations as $alloc) {
                        fputcsv($handle, [
                            $session->session_number,
                            $alloc->studentProfile->user->name ?? 'N/A',
                            $alloc->studentProfile->matric_number ?? 'N/A',
                            $alloc->hall->name ?? 'N/A',
                            $alloc->system->system_code ?? 'N/A',
                            $alloc->seat_status->label(),
                        ]);
                    }
                }
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
