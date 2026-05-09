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

                <form 
                    action="{{ route('predictions.generate', $model->id) }}" 
                    method="POST"
                    data-busy
                    data-busy-title="Generating Prediction..."
                    data-busy-message="The system is processing the selected trained model and generating the future stock price prediction. Please wait."
                >
                    @csrf

                    <div class="prediction-input-group">
                        <label for="future_date" class="prediction-label">Enter Target Future Date</label>
                        <input
                            type="text"
                            name="future_date"
                            id="future_date"
                            value="{{ old('future_date') }}"
                            class="form-control"
                            placeholder="Select prediction date"
                            required
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

<div id="busyOverlay" class="busy-overlay">
    <div class="busy-dialog">
        <div class="busy-spinner"></div>

        <h2 id="busyTitle" class="busy-title">Processing...</h2>

        <p id="busyMessage" class="busy-message">
            Please wait while the system processes your request.
        </p>

        <div class="busy-note">
            Please do not refresh or close this page.
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    /*
    |--------------------------------------------------------------------------
    | Disable Saturday and Sunday in Prediction Date Picker
    |--------------------------------------------------------------------------
    */
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#future_date", {
            dateFormat: "Y-m-d",
            disable: [
                function(date) {
                    // 0 = Sunday, 6 = Saturday
                    return date.getDay() === 0 || date.getDay() === 6;
                }
            ],
            locale: {
                firstDayOfWeek: 1
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Busy Dialog
    |--------------------------------------------------------------------------
    */
    function showBusyDialog(title, message) {
        const overlay = document.getElementById('busyOverlay');
        const busyTitle = document.getElementById('busyTitle');
        const busyMessage = document.getElementById('busyMessage');

        if (!overlay || !busyTitle || !busyMessage) {
            return;
        }

        busyTitle.textContent = title;
        busyMessage.textContent = message;
        overlay.classList.add('active');
    }

    document.querySelectorAll('form[data-busy]').forEach(function (form) {
        form.addEventListener('submit', function () {
            const title = form.dataset.busyTitle || 'Processing...';
            const message = form.dataset.busyMessage || 'Please wait while the system processes your request.';

            showBusyDialog(title, message);

            const submitButton = form.querySelector('button[type="submit"]');

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Processing...';
            }
        });
    });
});
</script>
@endsection