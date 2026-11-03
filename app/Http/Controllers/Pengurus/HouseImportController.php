<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Services\HouseCsvService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HouseImportController extends Controller
{
    public function index()
    {
        return view('pengurus.house-imports.index');
    }

    public function template()
    {
        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, HouseCsvService::COLUMNS, ',', '"', '');
            fclose($output);
        }, 'template-rumah.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request, HouseCsvService $service)
    {
        $request->validate(['csv' => ['required', 'file', 'max:2048']]);
        if (mb_strtolower($request->file('csv')->getClientOriginalExtension()) !== 'csv') {
            throw ValidationException::withMessages(['csv' => 'Gunakan file .csv.']);
        }

        return response()->view('pengurus.house-imports.preview', $service->preview($request->file('csv')->getRealPath(), $request->user()))->header('Cache-Control', 'private, no-store');
    }

    public function confirm(Request $request, string $batch, HouseCsvService $service)
    {
        $result = $service->confirm($batch, $request->user());
        $message = $result['replayed'] ? 'Batch ini sudah diproses. Tidak ada perubahan tambahan.'
            : "Impor selesai: {$result['created']} rumah baru, {$result['updated']} rumah diperbarui.";

        return redirect()->route('pengurus.houses.index')->with('success', $message);
    }
}
