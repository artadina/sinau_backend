<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Services\AdminResultsService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultController extends Controller
{
    // FR-A08, AC-39
    public function index(Request $request, AdminResultsService $results): View
    {
        $filters = $this->validatedFilters($request);

        return view('admin.results.index', [
            'title' => 'Rekap Hasil',
            'rows' => $results->rows($filters),
            'modules' => Module::query()->orderBy('title')->get(['id', 'title']),
            'filters' => $filters,
        ]);
    }

    // FR-A09, AC-39: export all rows matching the same filters as the table.
    public function export(Request $request, AdminResultsService $results): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $rows = $results->rows($filters, paginate: false);

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Pengguna', 'Modul', 'Skor pre', 'Skor post', 'Selisih', 'Unit selesai']);

            /** @var Collection<int, array<string, string>> $rows */
            foreach ($rows as $row) {
                fputcsv($output, array_map(
                    fn (string $cell): string => $this->neutralizeCsvFormula($cell),
                    array_values($row),
                ));
            }

            fclose($output);
        }, 'rekap-hasil.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // FR-A08, FR-A09: share validated query parameters between the table and export.
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'module_id' => ['nullable', 'integer', 'exists:modules,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ], [
            'module_id.exists' => 'Modul yang dipilih tidak ditemukan.',
            'date_from.date' => 'Tanggal mulai tidak valid.',
            'date_to.date' => 'Tanggal selesai tidak valid.',
            'date_to.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);
    }

    // FR-A09: neutralize formula-like cells before they reach spreadsheet software.
    private function neutralizeCsvFormula(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
