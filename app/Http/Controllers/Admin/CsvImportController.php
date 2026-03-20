<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\CsvImportService;
use Illuminate\Http\Request;

class CsvImportController extends Controller
{
    public function index()
    {
        return view('admin.import.index');
    }

    public function importStudents(Request $request, CsvImportService $service)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'academic_session' => 'required|string|max:20',
            'semester' => 'required|in:first,second',
            'import_type' => 'required|in:students,enrollments',
        ]);

        $file = $request->file('csv_file');
        $session = $request->input('academic_session');
        $semester = $request->input('semester');

        $results = match ($request->input('import_type')) {
            'students' => $service->importStudents($file, $session, $semester),
            'enrollments' => $service->importEnrollments($file, $session, $semester),
        };

        return back()->with('import_results', $results);
    }

    public function downloadTemplate(string $type)
    {
        $headers = match ($type) {
            'students' => ['name', 'email', 'matric_number', 'department_code', 'level', 'courses'],
            'enrollments' => ['matric_number', 'course_code'],
            default => abort(404),
        };

        $example = match ($type) {
            'students' => ['John Doe', 'john@uni.edu', 'CSE/2024/001', 'CSE', '300', 'CSE301|CSE401'],
            'enrollments' => ['CSE/2024/001', 'CSE301'],
        };

        return response()->streamDownload(function () use ($headers, $example) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            fputcsv($handle, $example);
            fclose($handle);
        }, "{$type}-template.csv", ['Content-Type' => 'text/csv']);
    }
}
