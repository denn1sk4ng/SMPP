<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prediction extends Model
{
    use HasFactory;

    protected $table = 'predictions';

    protected $fillable = [
        'user_id',
        'dataset_id',
        'trained_model_id',
        'result_path',
        'future_lstm_csv',
        'future_lr_csv',
        'future_ma_csv',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dataset()
    {
        return $this->belongsTo(Dataset::class, 'dataset_id');
    }

    public function trainedModel()
    {
        return $this->belongsTo(TrainedModel::class, 'trained_model_id');
    }
}