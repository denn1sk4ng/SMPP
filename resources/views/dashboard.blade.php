@extends('layouts.app')

@section('content')
<div class="dashboard-page">
    <div class="dashboard-content">
        <div class="dashboard-top-row">
            <div class="dashboard-heading">
                <h1 class="dashboard-title">Dashboard</h1>

                @auth
                    <div class="dashboard-user-greeting">
                        Hello, {{ auth()->user()->last_name ?? auth()->user()->name }}.
                    </div>
                @endauth

                <p class="dashboard-subtitle">
                    AI-powered stock market prediction overview
                </p>
            </div>

            <div class="dashboard-actions">
                <a href="{{ route('datasets.create') }}" class="dashboard-run-btn">Run Analysis</a>
            </div>
        </div>

        <div class="dashboard-card dashboard-summary-card">
            <h2>System Overview</h2>

            <div class="dashboard-summary-grid">
                <div>
                    <p class="metric-label">Total Trained Models</p>
                    <p class="metric-value">{{ $totalModels }}</p>
                </div>

                <div>
                    <p class="metric-label">Total Predictions</p>
                    <p class="metric-value">{{ $totalPredictions }}</p>
                </div>

                <div>
                    <p class="metric-label">Latest Best Model</p>
                    <p class="metric-value">{{ $latestModel->best_model ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="dashboard-grid-two">
            <div class="dashboard-card market-card">
                <h3>{{ $latestDataset->ticker ?? 'No Dataset Yet' }}</h3>
                <p class="market-main-value">{{ $latestDataset->file_name ?? 'No dataset yet' }}</p>
                <p class="market-sub-value">{{ $latestDataset->status ?? 'N/A' }}</p>
            </div>

            <div class="dashboard-card market-card">
                <h3>{{ $latestModel->model_name ?? 'No Model Yet' }}</h3>
                <p class="market-main-value">{{ $latestModel->best_model ?? 'N/A' }}</p>
                <p class="market-sub-value">
                    {{ $latestModel ? 'Latest trained model' : 'No trained model available' }}
                </p>
            </div>
        </div>

        <div class="dashboard-grid-two">
            <div class="dashboard-card">
                <h2>AI Market Insights</h2>

                <div class="insight-box success-box">
                    Latest dataset: {{ $latestDataset->ticker ?? 'N/A' }}
                </div>

                <div class="insight-box warning-box">
                    Latest prediction status: {{ $latestPrediction->status ?? 'N/A' }}
                </div>
            </div>

            <div class="dashboard-card">
                <h2>Performance Metrics</h2>

                <div class="metric-row">
                    <span>LSTM Average RMSE</span>
                    <span>{{ $averageLstmRmse ? number_format($averageLstmRmse, 2) : 'N/A' }}</span>
                </div>

                <div class="metric-row">
                    <span>Linear Regression Avg RMSE</span>
                    <span>{{ $averageLrRmse ? number_format($averageLrRmse, 2) : 'N/A' }}</span>
                </div>

                <div class="metric-row">
                    <span>Moving Average Avg RMSE</span>
                    <span>{{ $averageMaRmse ? number_format($averageMaRmse, 2) : 'N/A' }}</span>
                </div>

                <div class="metric-row">
                    <span>Predictions Made</span>
                    <span>{{ $totalPredictions }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection