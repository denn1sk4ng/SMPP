<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use App\Models\TrainedModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PredictionController extends Controller
{
    public function index()
    {
        $predictions = Prediction::where('user_id', auth()->id())
            ->orderBy('created_at', 'asc')
            ->get();

        return view('predictions.index', compact('predictions'));
    }

    public function create($modelId)
    {
        $model = TrainedModel::where('user_id', auth()->id())
            ->findOrFail($modelId);

        $dataset = $model->dataset;

        if (!$dataset || $dataset->user_id !== auth()->id()) {
            abort(403);
        }

        $lastDatasetDate = $this->getLastDatasetDate($dataset);

        return view('predictions.create', compact('model', 'lastDatasetDate'));
    }

    public function generate(Request $request, $modelId)
    {
        $request->validate([
            'future_date' => ['required', 'date'],
        ]);

        $model = TrainedModel::where('user_id', auth()->id())
            ->findOrFail($modelId);

        $dataset = $model->dataset;

        if (!$dataset || $dataset->user_id !== auth()->id()) {
            abort(403);
        }

        $csvPath = storage_path('app/' . $dataset->file_path);

        if (!file_exists($csvPath)) {
            return back()->with('error', 'Dataset file was not found.');
        }

        $lastDatasetDate = $this->getLastDatasetDate($dataset);

        if ($lastDatasetDate) {
            $futureDate = Carbon::parse($request->future_date)->startOfDay();
            $lastDate = Carbon::parse($lastDatasetDate)->startOfDay();

            if ($futureDate->lessThanOrEqualTo($lastDate)) {
                return back()->with('error', 'Please select a future date after the last date in the dataset.');
            }
        }

        $pythonExe = base_path('.venv/Scripts/python.exe');
        $pythonScript = base_path('python_backend/predict_only.py');
        $assetsDir = base_path('python_backend/assets');
        $resultsDir = base_path('python_backend/results');

        if (!file_exists($pythonExe)) {
            return back()->with('error', 'Python environment was not found.');
        }

        if (!file_exists($pythonScript)) {
            return back()->with('error', 'Prediction script was not found.');
        }

        if (!is_dir($assetsDir)) {
            mkdir($assetsDir, 0755, true);
        }

        if (!is_dir($resultsDir)) {
            mkdir($resultsDir, 0755, true);
        }

        $command =
            escapeshellarg($pythonExe) . ' ' .
            escapeshellarg($pythonScript) . ' ' .
            '--csv ' . escapeshellarg($csvPath) . ' ' .
            '--assets-dir ' . escapeshellarg($assetsDir) . ' ' .
            '--results-dir ' . escapeshellarg($resultsDir) . ' ' .
            '--future-date ' . escapeshellarg($request->future_date) . ' 2>&1';

        $outputText = shell_exec($command);

        if ($outputText === null) {
            return back()->with('error', 'Python command returned no output.');
        }

        $jsonStart = strpos($outputText, '{');
        $jsonEnd = strrpos($outputText, '}');

        if ($jsonStart === false || $jsonEnd === false || $jsonEnd <= $jsonStart) {
            Log::error('Prediction JSON parse failed.', [
                'output' => $outputText,
            ]);

            return back()->with('error', 'Prediction failed because the Python output could not be parsed.');
        }

        $jsonText = substr($outputText, $jsonStart, $jsonEnd - $jsonStart + 1);
        $output = json_decode($jsonText, true);

        if (!$output || !isset($output['status'])) {
            Log::error('Invalid JSON from prediction script.', [
                'output' => $outputText,
            ]);

            return back()->with('error', 'Prediction failed because the Python script returned invalid data.');
        }

        if ($output['status'] !== 'success') {
            Log::error('Prediction script failed.', [
                'output' => $outputText,
            ]);

            return back()->with('error', 'Prediction failed. Please try again.');
        }

        $prediction = Prediction::create([
            'user_id' => auth()->id(),
            'dataset_id' => $dataset->id,
            'trained_model_id' => $model->id,
            'result_path' => $output['test_results_csv'] ?? null,
            'future_lstm_csv' => $output['future_forecast']['lstm_csv'] ?? null,
            'future_lr_csv' => $output['future_forecast']['lr_csv'] ?? null,
            'future_ma_csv' => $output['future_forecast']['ma_csv'] ?? null,
            'status' => 'generated',
        ]);

        return redirect()
            ->route('predictions.show', $prediction->id)
            ->with('success', 'Prediction generated successfully.');
    }

    public function show($id)
    {
        $prediction = Prediction::where('user_id', auth()->id())
            ->findOrFail($id);

        $dataset = $prediction->dataset;

        if (!$dataset || $dataset->user_id !== auth()->id()) {
            abort(403);
        }

        $lastKnownClose = $this->getLastKnownClose($dataset);

        $futureFiles = [
            'LSTM' => $prediction->future_lstm_csv,
            'Linear Regression' => $prediction->future_lr_csv,
            'Moving Average' => $prediction->future_ma_csv,
        ];

        $trendSummary = $this->buildTrendSummary($futureFiles, $lastKnownClose);

        return view('predictions.show', compact(
            'prediction',
            'lastKnownClose',
            'trendSummary'
        ));
    }

    public function exportTxt($id)
    {
        $prediction = Prediction::where('user_id', auth()->id())
            ->findOrFail($id);

        $dataset = $prediction->dataset;

        if (!$dataset || $dataset->user_id !== auth()->id()) {
            abort(403);
        }

        $lastKnownClose = $this->getLastKnownClose($dataset);

        $futureFiles = [
            'LSTM' => $prediction->future_lstm_csv,
            'Linear Regression' => $prediction->future_lr_csv,
            'Moving Average' => $prediction->future_ma_csv,
        ];

        $trendSummary = $this->buildTrendSummary($futureFiles, $lastKnownClose);

        $content = "Stock Market Price Prediction Result\n";
        $content .= "Prediction ID: {$prediction->id}\n";
        $content .= "Status: {$prediction->status}\n";
        $content .= "Last Known Close: " . ($lastKnownClose !== null ? round($lastKnownClose, 2) : 'N/A') . "\n\n";
        $content .= "Overall Market Trend Prediction\n";
        $content .= "--------------------------------------------\n";

        if (empty($trendSummary)) {
            $content .= "No prediction summary available.\n";
            $content .= "Please check whether the prediction CSV files contain valid forecast values.\n";
        } else {
            foreach ($trendSummary as $modelName => $summary) {
                $content .= "Model: {$modelName}\n";
                $content .= "Forecast Date: {$summary['forecast_date']}\n";
                $content .= "Predicted Close Price: {$summary['predicted_close']}\n";
                $content .= "Percentage Change: {$summary['percentage_change']}%\n";
                $content .= "Market Trend: {$summary['trend']}\n";
                $content .= "--------------------------------------------\n";
            }
        }

        $fileName = 'prediction_result_' . $prediction->id . '.txt';

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function done($id)
    {
        Prediction::where('user_id', auth()->id())
            ->findOrFail($id);

        return redirect()
            ->route('home')
            ->with('success', 'You can now select another model or upload a new dataset.');
    }

    public function destroy($id)
    {
        $prediction = Prediction::where('user_id', auth()->id())
            ->findOrFail($id);

        $paths = [
            $prediction->result_path,
            $prediction->future_lstm_csv,
            $prediction->future_lr_csv,
            $prediction->future_ma_csv,
        ];

        foreach ($paths as $path) {
            if ($path && file_exists($path) && $this->isSafeGeneratedPath($path)) {
                @unlink($path);
            }
        }

        $prediction->delete();

        return redirect()
            ->route('predictions.index')
            ->with('success', 'Prediction deleted successfully.');
    }

    private function getLastKnownClose($dataset)
    {
        if (!$dataset) {
            return null;
        }

        $csvPath = storage_path('app/' . $dataset->file_path);

        if (!file_exists($csvPath)) {
            return null;
        }

        $rows = array_map('str_getcsv', file($csvPath));

        if (count($rows) < 2) {
            return null;
        }

        $header = array_map('trim', $rows[0]);

        $closeIndex = array_search('Close', $header);

        if ($closeIndex === false) {
            $closeIndex = array_search('Adj Close', $header);
        }

        if ($closeIndex === false) {
            return null;
        }

        for ($i = count($rows) - 1; $i >= 1; $i--) {
            $row = $rows[$i];

            if (isset($row[$closeIndex]) && is_numeric($row[$closeIndex])) {
                return (float) $row[$closeIndex];
            }
        }

        return null;
    }

    private function getLastDatasetDate($dataset)
    {
        if (!$dataset) {
            return null;
        }

        $csvPath = storage_path('app/' . $dataset->file_path);

        if (!file_exists($csvPath)) {
            return null;
        }

        $rows = array_map('str_getcsv', file($csvPath));

        if (count($rows) < 2) {
            return null;
        }

        $header = array_map('trim', $rows[0]);
        $dateIndex = array_search('Date', $header);

        if ($dateIndex === false) {
            return null;
        }

        for ($i = count($rows) - 1; $i >= 1; $i--) {
            $row = $rows[$i];

            if (isset($row[$dateIndex]) && trim($row[$dateIndex]) !== '') {
                return trim($row[$dateIndex]);
            }
        }

        return null;
    }

    private function readLastFuturePrediction($csvPath)
    {
        if (!$csvPath || !file_exists($csvPath)) {
            return null;
        }

        if (!$this->isSafeGeneratedPath($csvPath)) {
            return null;
        }

        $rows = array_map('str_getcsv', file($csvPath));

        if (count($rows) < 2) {
            return null;
        }

        $header = array_map('trim', $rows[0]);

        for ($i = count($rows) - 1; $i >= 1; $i--) {
            $row = $rows[$i];

            if (empty($row)) {
                continue;
            }

            $data = [];

            if (count($row) === count($header)) {
                $data = array_combine($header, $row);
            }

            $forecastDate =
                $data['Forecast_Date']
                ?? $data['Forecast Date']
                ?? $data['Date']
                ?? $data['Prediction_Date']
                ?? $data['Prediction Date']
                ?? ($row[0] ?? 'N/A');

            $predictedClose =
                $data['Predicted_Close_Price']
                ?? $data['Predicted Close Price']
                ?? $data['Predicted_Close']
                ?? $data['Predicted Close']
                ?? $data['Close']
                ?? $data['LSTM_Predicted_Close']
                ?? $data['LR_Predicted_Close']
                ?? $data['MA_Predicted_Close']
                ?? ($row[2] ?? null);

            if ($predictedClose !== null && is_numeric($predictedClose)) {
                return [
                    'forecast_date' => $forecastDate,
                    'predicted_close' => (float) $predictedClose,
                ];
            }
        }

        return null;
    }

    private function buildTrendSummary(array $futureFiles, $lastKnownClose): array
    {
        $trendSummary = [];

        foreach ($futureFiles as $modelName => $csvPath) {
            $futureData = $this->readLastFuturePrediction($csvPath);

            if (!$futureData || !$lastKnownClose) {
                continue;
            }

            $predictedClose = $futureData['predicted_close'];

            if ($predictedClose <= 0) {
                continue;
            }

            $percentageChange = (($predictedClose - $lastKnownClose) / $lastKnownClose) * 100;

            if ($percentageChange > 0.5) {
                $trend = 'Uptrend';
            } elseif ($percentageChange < -0.5) {
                $trend = 'Downtrend';
            } else {
                $trend = 'Sideways';
            }

            $trendSummary[$modelName] = [
                'forecast_date' => $futureData['forecast_date'],
                'predicted_close' => round($predictedClose, 2),
                'percentage_change' => round($percentageChange, 2),
                'trend' => $trend,
            ];
        }

        return $trendSummary;
    }

    private function isSafeGeneratedPath(string $path): bool
    {
        $realPath = realpath($path);

        if (!$realPath) {
            return false;
        }

        $allowedDirectories = [
            realpath(base_path('python_backend/results')),
            realpath(base_path('python_backend/assets')),
        ];

        foreach ($allowedDirectories as $directory) {
            if ($directory && str_starts_with($realPath, $directory)) {
                return true;
            }
        }

        return false;
    }
}