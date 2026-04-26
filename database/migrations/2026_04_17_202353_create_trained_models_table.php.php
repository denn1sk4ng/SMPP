<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trained_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('dataset_id')->constrained('datasets')->onDelete('cascade');

            $table->string('model_name');
            $table->string('model_type')->nullable();
            $table->integer('time_step')->default(30);

            $table->string('asset_path')->nullable();
            $table->string('results_path')->nullable();

            $table->decimal('lstm_train_rmse', 12, 6)->nullable();
            $table->decimal('lstm_test_rmse', 12, 6)->nullable();
            $table->decimal('lr_rmse', 12, 6)->nullable();
            $table->decimal('ma_rmse', 12, 6)->nullable();

            $table->string('best_model')->nullable();
            $table->string('status')->default('trained');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trained_models');
    }
};