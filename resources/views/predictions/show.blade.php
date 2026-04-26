@extends('layouts.app')

@section('content')
<div class="module-page">
    <div class="module-wrapper">
        <div class="module-card">
            <h1 class="module-title">Prediction Result</h1>
            <p class="module-subtitle">
                Review the forecasted price, percentage change, and derived market trend.
            </p>

            @if(!empty($trendSummary))
                <p style="margin-bottom: 16px;">
                    <strong>Last Known Close:</strong>
                    {{ $lastKnownClose !== null ? number_format($lastKnownClose, 2) : 'N/A' }}
                </p>

                <div class="prediction-result-grid">
                    @foreach($trendSummary as $modelName => $summary)
                        <div class="prediction-result-card">
                            <h3>{{ $modelName }}</h3>

                            <p class="prediction-meta">
                                Forecast Date:
                                <strong>{{ $summary['forecast_date'] ?? 'N/A' }}</strong>
                            </p>

                            <div class="prediction-value">
                                {{ isset($summary['predicted_close']) ? number_format($summary['predicted_close'], 2) : 'N/A' }}
                            </div>

                            <p class="prediction-meta">
                                Percentage Change:
                                <strong>
                                    {{ isset($summary['percentage_change']) ? number_format($summary['percentage_change'], 2) . '%' : 'N/A' }}
                                </strong>
                            </p>
                            @php
                                $trend = trim($summary['trend'] ?? 'N/A');
                                $trendLower = strtolower($trend);

                                if (str_contains($trendLower, 'uptrend')) {
                                    $trendColor = '#22c55e';
                                } elseif (str_contains($trendLower, 'downtrend')) {
                                    $trendColor = '#ef4444';
                                } else {
                                    $trendColor = '#facc15';
                                }
                            @endphp

                            <p class="prediction-meta">
                                Market Trend:
                                <strong style="color: {{ $trendColor }} !important; font-weight: 800;">
                                    {{ $trend }}
                                </strong>
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="padding: 16px; border-radius: 10px; background: rgba(239, 68, 68, 0.12); color: #fecaca;">
                    No prediction values were found. Please check whether the prediction CSV paths were saved correctly.
                </div>
            @endif

            <div class="module-actions">
                <a href="{{ route('predictions.exportTxt', $prediction->id) }}" class="btn">
                    Export Result as .txt
                </a>

                <a href="{{ route('predictions.done', $prediction->id) }}" class="btn-secondary">
                    Done
                </a>
            </div>
        </div>
    </div>
</div>
@endsection