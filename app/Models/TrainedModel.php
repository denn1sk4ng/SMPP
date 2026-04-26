<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainedModel extends Model
{
    use HasFactory;

    protected $table = 'trained_models';

    protected $fillable = [
        'user_id',
        'dataset_id',
        'model_name',
        'model_type',
        'time_step',
        'asset_path',
        'results_path',
        'chart_path',
        'lstm_train_rmse',
        'lstm_test_rmse',
        'lr_rmse',
        'ma_rmse',
        'best_model',
        'status',
    ];

    protected $casts = [
        'lstm_train_rmse' => 'float',
        'lstm_test_rmse' => 'float',
        'lr_rmse' => 'float',
        'ma_rmse' => 'float',
        'time_step' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dataset()
    {
        return $this->belongsTo(Dataset::class, 'dataset_id');
    }

    public function predictions()
    {
        return $this->hasMany(Prediction::class, 'trained_model_id');
    }
}