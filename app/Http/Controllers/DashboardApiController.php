<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Models\Prediction;
use App\Models\TrainedModel;

class DashboardApiController extends Controller
{
    public function summary()
    {
        $userId = auth()->id();

        return response()->json([
            'total_models' => TrainedModel::where('user_id', $userId)->count(),
            'total_predictions' => Prediction::where('user_id', $userId)->count(),
            'latest_dataset' => Dataset::where('user_id', $userId)->latest()->first(),
            'latest_model' => TrainedModel::where('user_id', $userId)->latest()->first(),
            'latest_prediction' => Prediction::where('user_id', $userId)->latest()->first(),
        ]);
    }
}