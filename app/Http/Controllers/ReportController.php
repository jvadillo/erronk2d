<?php

namespace App\Http\Controllers;

use App\Domain\AcademicContext;
use App\Domain\Grades\Gradebook;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, Gradebook $book): Response|StreamedResponse
    {
        abort_if($request->user()->role === 'student', 403);
        $query = app(AcademicContext::class)->classrooms($request->user());
        $classes = (clone $query)->with('academicYear')->get();
        $selected = $request->integer('classroom', $classes->first()?->id ?? 0);
        $report = $selected ? $book->report((clone $query)->findOrFail($selected)) : null;
        if ($request->query('format') === 'csv' && $report) {
            return response()->streamDownload(function () use ($report) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, ['Estudiante', 'Módulo', 'Evaluación', 'Nota', 'Media del curso'], ';', '"', '');
                foreach ($report['rows'] as $row) {
                    foreach ($row['periods'] as $period) {
                        $safe = fn ($s) => preg_match('/^[=+@\-\t\r]/u', (string) $s) ? "'".$s : $s;
                        fputcsv($out, array_map($safe, [$row['student'], $row['module'], $period['name'], $period['not_enrolled'] ? 'No matriculado' : ($period['grade'] ?? 'Pendiente'), $row['not_enrolled'] ? 'No matriculado' : ($row['annual'] ?? 'Pendiente')]), ';', '"', '');
                    }
                } fclose($out);
            }, 'erronk2d-evaluaciones.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return Inertia::render('Reports', ['classrooms' => $classes, 'report' => $report]);
    }
}
