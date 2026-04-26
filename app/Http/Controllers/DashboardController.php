<?php

namespace App\Http\Controllers;

use App\Models\Dataset;
use App\Models\Prediction;
use App\Models\TrainedModel;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $totalModels = TrainedModel::where('user_id', $userId)->count();
        $totalPredictions = Prediction::where('user_id', $userId)->count();

        $latestModel = TrainedModel::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        $latestPrediction = Prediction::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        $latestDataset = Dataset::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();

        $averageLstmRmse = TrainedModel::where('user_id', $userId)
            ->whereNotNull('lstm_test_rmse')
            ->avg('lstm_test_rmse');

        $averageLrRmse = TrainedModel::where('user_id', $userId)
            ->whereNotNull('lr_rmse')
            ->avg('lr_rmse');

        $averageMaRmse = TrainedModel::where('user_id', $userId)
            ->whereNotNull('ma_rmse')
            ->avg('ma_rmse');

        $bestModelCounts = TrainedModel::where('user_id', $userId)
            ->selectRaw('best_model, COUNT(*) as total')
            ->groupBy('best_model')
            ->pluck('total', 'best_model');

        return view('dashboard', compact(
            'totalModels',
            'totalPredictions',
            'latestModel',
            'latestPrediction',
            'latestDataset',
            'averageLstmRmse',
            'averageLrRmse',
            'averageMaRmse',
            'bestModelCounts'
        ));
    }
}