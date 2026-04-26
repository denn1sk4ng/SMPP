@extends('layouts.app')

@section('content')
<div class="prediction-hero-section">
    <div class="prediction-hero-content">
        <h2 class="prediction-subtitle">Training</h2>
        <h1 class="prediction-title">Result</h1>

        <div class="prediction-form-card">
            <p class="prediction-model-info">
                <strong>Model Name:</strong> {{ $model->model_name }}
            </p>

            <p class="prediction-model-info">
                <strong>Model Type:</strong> {{ $model->model_type }}
            </p>

            <p class="prediction-model-info">
                <strong>LSTM Train RMSE:</strong> {{ $model->lstm_train_rmse }}
            </p>

            <p class="prediction-model-info">
                <strong>LSTM Test RMSE:</strong> {{ $model->lstm_test_rmse }}
            </p>

            <p class="prediction-model-info">
                <strong>Linear Regression RMSE:</strong> {{ $model->lr_rmse }}
            </p>

            <p class="prediction-model-info">
                <strong>Moving Average RMSE:</strong> {{ $model->ma_rmse }}
            </p>

            <p class="prediction-model-info">
                <strong>Best Model:</strong> {{ $model->best_model }}
            </p>

            @if($model->chart_path)
                <div class="training-chart-wrapper">
                    <img
                        src="{{ route('models.chart', $model->id) }}"
                        alt="Training Result Chart"
                        class="training-chart-img"
                        id="trainingChartImg"
                    >

                    <p class="chart-zoom-hint">Click chart to enlarge</p>
                </div>
            @endif

            <div class="training-result-actions">
                <a href="{{ route('models.index') }}" class="btn">
                    Next
                </a>

                <a href="{{ route('home') }}" class="btn-secondary">
                    Done
                </a>

                <form
                    action="{{ route('models.destroy', $model->id) }}"
                    method="POST"
                    onsubmit="return confirm('Are you sure you want to cancel this training result?');"
                >
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="from" value="training_result">

                    <button type="submit" class="btn-danger">
                        Cancel Training
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="chartZoomModal" class="chart-zoom-modal" style="display: none;">
    <button type="button" id="closeChartZoom" class="chart-zoom-close">
        ×
    </button>

    <img
        src="{{ route('models.chart', $model->id) }}"
        alt="Training Result Chart Zoomed"
        class="chart-zoom-img"
    >
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartImg = document.getElementById('trainingChartImg');
        const modal = document.getElementById('chartZoomModal');
        const closeBtn = document.getElementById('closeChartZoom');

        if (chartImg && modal && closeBtn) {
            chartImg.addEventListener('click', function () {
                modal.style.display = 'flex';
            });

            closeBtn.addEventListener('click', function () {
                modal.style.display = 'none';
            });

            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    modal.style.display = 'none';
                }
            });
        }
    });
</script>
@endsection