<?php

namespace App\Http\Controllers;

use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DatasetController extends Controller
{
    public function create()
    {
        return view('datasets.upload');
    }

    public function store(Request $request)
    {
        $request->validate([
            'dataset' => 'required|file|mimes:csv,txt|max:20480',
        ]);

        $file = $request->file('dataset');

        if (!$file || !$file->isValid()) {
            return back()->with('error', 'Uploaded file is invalid.');
        }

        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        $safeBaseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeBaseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $safeBaseName);
        $safeBaseName = preg_replace('/_+/', '_', $safeBaseName);
        $safeBaseName = trim($safeBaseName, '_');

        if ($safeBaseName === '') {
            $safeBaseName = 'uploaded_dataset';
        }

        $safeFileName = $safeBaseName . '_' . auth()->id() . '_' . time() . '_' . Str::random(6) . '.' . $extension;
        $fallbackTicker = strtoupper($safeBaseName);

        $path = $file->storeAs('datasets', $safeFileName, 'local');

        if (!$path) {
            return back()->with('error', 'Failed to save uploaded file.');
        }

        if (!Storage::disk('local')->exists($path)) {
            return back()->with('error', 'File path was generated, but the file was not saved.');
        }

        $dataset = Dataset::create([
            'user_id' => auth()->id(),
            'file_name' => $safeFileName,
            'ticker' => $fallbackTicker,
            'file_path' => $path,
            'status' => 'uploaded',
        ]);

        return redirect()
            ->route('datasets.preview', $dataset->id)
            ->with('success', 'Dataset uploaded successfully.');
    }

    public function fetch(Request $request)
    {
        $request->validate([
            'preset' => ['nullable', 'string', 'in:sp500,apple,microsoft,tesla,nvidia,amazon,google,meta'],
            'ticker' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\.\^\-_]+$/'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        if (!$request->filled('preset') && !$request->filled('ticker')) {
            return back()->with('error', 'Please select a preset or enter a ticker symbol.');
        }

        $pythonExe = base_path('.venv/Scripts/python.exe');
        $pythonScript = base_path('python_backend/fetch_dataset.py');

        /*
         | Keep fetched datasets in the same Laravel local disk location
         | as manual uploads.
         */
        $outputDir = Storage::disk('local')->path('datasets');

        if (!file_exists($pythonExe)) {
            return back()->with('error', 'Python environment was not found.');
        }

        if (!file_exists($pythonScript)) {
            return back()->with('error', 'Dataset fetch script was not found.');
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $command =
            escapeshellarg($pythonExe) . ' ' .
            escapeshellarg($pythonScript) . ' ' .
            '--start-date ' . escapeshellarg($request->start_date) . ' ' .
            '--end-date ' . escapeshellarg($request->end_date) . ' ' .
            '--output-dir ' . escapeshellarg($outputDir) . ' ';

        if ($request->filled('preset')) {
            $command .= '--preset ' . escapeshellarg($request->preset) . ' ';
        }

        if ($request->filled('ticker')) {
            $command .= '--ticker ' . escapeshellarg(strtoupper($request->ticker)) . ' ';
        }

        $command .= '2>&1';

        $outputText = shell_exec($command);

        if ($outputText === null) {
            return back()->with('error', 'Dataset fetch script returned no output.');
        }

        $jsonStart = strpos($outputText, '{');
        $jsonEnd = strrpos($outputText, '}');

        if ($jsonStart === false || $jsonEnd === false || $jsonEnd <= $jsonStart) {
            Log::error('Dataset fetch JSON parse failed.', [
                'output' => $outputText,
            ]);

            return back()->with('error', 'Dataset fetch failed because the Python output could not be parsed.');
        }

        $jsonText = substr($outputText, $jsonStart, $jsonEnd - $jsonStart + 1);
        $output = json_decode($jsonText, true);

        if (!$output || !isset($output['status']) || $output['status'] !== 'success') {
            Log::error('Dataset fetch script failed.', [
                'output' => $outputText,
            ]);

            return back()->with('error', 'Dataset fetch failed. Please check the ticker and date range.');
        }

        if (empty($output['file_name'])) {
            return back()->with('error', 'Dataset fetch failed because no file name was returned.');
        }

        $safeOutputFileName = basename($output['file_name']);
        $relativePath = 'datasets/' . $safeOutputFileName;

        if (!Storage::disk('local')->exists($relativePath)) {
            return back()->with('error', 'Fetched dataset file was not found.');
        }

        $dataset = Dataset::create([
            'user_id' => auth()->id(),
            'file_name' => $safeOutputFileName,
            'ticker' => $output['ticker'] ?? strtoupper($request->ticker ?? $request->preset ?? 'UNKNOWN'),
            'file_path' => $relativePath,
            'status' => 'fetched',
        ]);

        return redirect()
            ->route('datasets.preview', $dataset->id)
            ->with('success', 'Dataset fetched successfully.');
    }

    public function preview($id)
    {
        $dataset = Dataset::where('user_id', auth()->id())
            ->findOrFail($id);

        if (!Storage::disk('local')->exists($dataset->file_path)) {
            return back()->with('error', 'Dataset file was not found.');
        }

        $fullPath = Storage::disk('local')->path($dataset->file_path);

        if (!$this->isSafeDatasetPath($fullPath)) {
            abort(403);
        }

        $rows = array_map('str_getcsv', file($fullPath));

        return view('datasets.preview', compact('dataset', 'rows'));
    }

    public function download($id)
    {
        $dataset = Dataset::where('user_id', auth()->id())
            ->findOrFail($id);

        if (!Storage::disk('local')->exists($dataset->file_path)) {
            return back()->with('error', 'Dataset file was not found.');
        }

        $fullPath = Storage::disk('local')->path($dataset->file_path);

        if (!$this->isSafeDatasetPath($fullPath)) {
            abort(403);
        }

        return response()->download($fullPath, $dataset->file_name);
    }

    private function isSafeDatasetPath(string $path): bool
    {
        $realPath = realpath($path);
        $datasetDirectory = realpath(Storage::disk('local')->path('datasets'));

        if (!$realPath || !$datasetDirectory) {
            return false;
        }

        return str_starts_with($realPath, $datasetDirectory);
    }
}