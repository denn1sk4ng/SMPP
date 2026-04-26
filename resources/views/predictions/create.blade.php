@extends('layouts.app')

@section('content')
<div class="dashboard-page">
    @include('partials.header')

    <section class="prediction-hero-section">
        <div class="hero-overlay"></div>

        <div class="prediction-hero-content">
            <h2 class="prediction-subtitle">Generate a forecast</h2>
            <h1 class="prediction-title">Select a future date</h1>

            <div class="prediction-form-card">
                <p class="prediction-model-info"><strong>Selected Model:</strong> {{ $model->model_name }}</p>
                <p class="prediction-model-info"><strong>Model Type:</strong> {{ $model->model_type }}</p>
                <p class="prediction-model-info"><strong>Best Model During Training:</strong> {{ $model->best_model }}</p>
                <p class="prediction-model-info"><strong>Last Available Dataset Date:</strong> {{ $lastDatasetDate ?? 'N/A' }}</p>

                <form action="{{ route('predictions.generate', $model->id) }}" method="POST">
                    @csrf

                    <div class="prediction-input-group">
                        <label for="future_date" class="prediction-label">Enter Target Future Date</label>
                        <input
                            type="date"
                            name="future_date"
                            id="future_date"
                            class="prediction-date-input"
                            required
                            min="{{ $lastDatasetDate ? \Carbon\Carbon::parse($lastDatasetDate)->addDay()->format('Y-m-d') : '' }}"
                        >
                    </div>

                    <button type="submit" class="hero-button prediction-submit-btn">
                        Generate Prediction
                    </button>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection