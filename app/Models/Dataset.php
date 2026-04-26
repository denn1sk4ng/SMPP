<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    use HasFactory;

    protected $table = 'datasets';

    protected $fillable = [
        'user_id',
        'file_name',
        'ticker',
        'file_path',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trainedModels()
    {
        return $this->hasMany(TrainedModel::class, 'dataset_id');
    }

    public function predictions()
    {
        return $this->hasMany(Prediction::class, 'dataset_id');
    }
}