<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Services\ResidentCsvService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ResidentImportController extends Controller
{
    public function index()
    {
        return view('pengurus.imports.index');
    }

    public function template()
    {
        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ResidentCsvService::COLUMNS, ',', '"', '');
            fclose($output);
        }, 'template-warga.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request, ResidentCsvService $service)
    {
        $request->validate(['csv' => ['required', 'file', 'max:2048']]);
        if (mb_strtolower($request->file('csv')->getClientOriginalExtension()) !== 'csv') {
            throw ValidationException::withMessages(['csv' => 'Gunakan file .csv.']);
        }

        return response()->view('pengurus.imports.preview', $service->preview($request->file('csv')->getRealPath(), $request->user()))
            ->header('Cache-Control', 'private, no-store');
    }

    public function confirm(Request $request, string $batch, ResidentCsvService $service)
    {
        $result = $service->confirm($batch, $request->user());
        $message = $result['replayed'] ? 'Batch ini sudah diproses. Tidak ada perubahan tambahan.'
            : "Impor selesai: {$result['created']} akun warga baru, {$result['updated']} akun warga diperbarui.";

        return redirect()->route('pengurus.residents.index')->with('success', $message);
    }
}
