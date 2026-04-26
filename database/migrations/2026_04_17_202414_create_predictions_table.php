<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('dataset_id')->constrained('datasets')->onDelete('cascade');
            $table->foreignId('trained_model_id')->constrained('trained_models')->onDelete('cascade');

            $table->string('result_path')->nullable();
            $table->string('future_lstm_csv')->nullable();
            $table->string('future_lr_csv')->nullable();
            $table->string('future_ma_csv')->nullable();

            $table->string('status')->default('generated');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};