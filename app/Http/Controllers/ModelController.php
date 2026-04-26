<?php

namespace App\Http\Controllers;

use App\Models\Dataset;
use App\Models\TrainedModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ModelController extends Controller
{
    public function index()
    {
        $models = TrainedModel::where('user_id', auth()->id())
            ->orderBy('created_at', 'asc')
            ->get();

        return view('models.index', compact('models'));
    }

    public function train($datasetId)
    {
        $dataset = Dataset::where('user_id', auth()->id())
            ->findOrFail($datasetId);

        $csvPath = storage_path('app/' . $dataset->file_path);

        if (!file_exists($csvPath)) {
            return back()->with('error', 'Dataset file was not found.');
        }

        $pythonExe = base_path('.venv/Scripts/python.exe');
        $pythonScript = base_path('python_backend/train_and_predict.py');
        $assetsDir = base_path('python_backend/assets');
        $resultsDir = base_path('python_backend/results');

        if (!file_exists($pythonExe)) {
            return back()->with('error', 'Python environment was not found.');
        }

        if (!file_exists($pythonScript)) {
            return back()->with('error', 'Training script was not found.');
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
            '--results-dir ' . escapeshellarg($resultsDir) . ' 2>&1';

        $outputText = shell_exec($command);

        if ($outputText === null) {
            return back()->with('error', 'Python command returned no output.');
        }

        $jsonStart = strpos($outputText, '{');
        $jsonEnd = strrpos($outputText, '}');

        if ($jsonStart === false || $jsonEnd === false || $jsonEnd <= $jsonStart) {
            Log::error('Training JSON parse failed.', [
                'output' => $outputText,
            ]);

            return back()->with('error', 'Training failed because the Python output could not be parsed.');
        }

        $jsonText = substr($outputText, $jsonStart, $jsonEnd - $jsonStart + 1);
        $output = json_decode($jsonText, true);

        if (!$output || !isset($output['status'])) {
            Log::error('Invalid JSON from training script.', [
                'output' => $outputText,
            ]);

            return back()->with('error', 'Training failed because the Python script returned invalid data.');
        }

        if ($output['status'] !== 'success') {
            Log::error('Training script failed.', [
                'output' => $outputText,
            ]);

            return back()->with('error', 'Training failed. Please check your dataset and try again.');
        }

        $trainedModel = TrainedModel::create([
            'user_id' => auth()->id(),
            'dataset_id' => $dataset->id,
            'model_name' => $dataset->ticker ?? 'Unknown Ticker',
            'model_type' => 'LSTM',
            'time_step' => 30,
            'asset_path' => $output['assets_dir'] ?? $assetsDir,
            'results_path' => $output['results_csv'] ?? null,
            'chart_path' => $output['chart_path'] ?? null,
            'lstm_train_rmse' => $output['metrics']['lstm_train_rmse'] ?? null,
            'lstm_test_rmse' => $output['metrics']['lstm_test_rmse'] ?? null,
            'lr_rmse' => $output['metrics']['linear_regression_rmse'] ?? null,
            'ma_rmse' => $output['metrics']['moving_average_rmse'] ?? null,
            'best_model' => $output['best_model'] ?? null,
            'status' => 'trained',
        ]);

        return redirect()
            ->route('models.trainingResult', $trainedModel->id)
            ->with('success', 'Models trained successfully.');
    }

    public function trainingResult($id)
    {
        $model = TrainedModel::where('user_id', auth()->id())
            ->findOrFail($id);

        return view('models.training-result', compact('model'));
    }

    public function chart($id)
    {
        $model = TrainedModel::where('user_id', auth()->id())
            ->findOrFail($id);

        if (!$model->chart_path || !file_exists($model->chart_path)) {
            abort(404);
        }

        if (!$this->isSafeGeneratedPath($model->chart_path)) {
            abort(403);
        }

        return response()->file($model->chart_path);
    }

    public function destroy(Request $request, $id)
    {
        $model = TrainedModel::where('user_id', auth()->id())
            ->findOrFail($id);

        if ($model->chart_path && file_exists($model->chart_path) && $this->isSafeGeneratedPath($model->chart_path)) {
            @unlink($model->chart_path);
        }

        if ($model->results_path && file_exists($model->results_path) && $this->isSafeGeneratedPath($model->results_path)) {
            @unlink($model->results_path);
        }

        $model->delete();

        if ($request->input('from') === 'training_result') {
            return redirect()
                ->route('datasets.create')
                ->with('success', 'Training cancelled and removed successfully.');
        }

        return redirect()
            ->route('models.index')
            ->with('success', 'Trained model deleted successfully.');
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