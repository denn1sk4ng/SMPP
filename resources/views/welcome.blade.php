@extends('layouts.app')

@section('content')
<div class="home-page">
    <section class="home-main-section">
        <div class="home-content-wrapper">

            <div class="hero-content">
                @if(session('welcome_message'))
                    <div class="welcome-user-message">
                        {{ session('welcome_message') }}
                    </div>
                @endif

                <h2 class="hero-subtitle">AI-Powered</h2>
                <h1 class="hero-title">Stock Predictions</h1>

                @auth
                    <a href="{{ route('datasets.create') }}" class="hero-button">Start Prediction</a>
                @else
                    <a href="{{ route('login') }}" class="hero-button">Get Started</a>
                @endauth
            </div>

            <div class="home-info-container">
                <div class="home-info-card scroll-fade-card">
                    <h2 class="home-section-title">About SMPP</h2>
                    <p class="home-section-text">
                        SMPP is a web-based stock market price prediction system designed to forecast future stock prices
                        using Long Short-Term Memory (LSTM). The system compares the performance of LSTM with
                        Linear Regression and Moving Average to provide a more reliable evaluation of prediction accuracy.
                    </p>
                    <p class="home-section-text">
                        Users can fetch stock datasets automatically using a ticker symbol and date range, preview the
                        dataset, train the prediction models, view training performance results, generate future-date
                        predictions, and interpret the overall market direction based on percentage change from the latest
                        available closing price.
                    </p>
                </div>

                <div class="home-info-card scroll-fade-card">
                    <h2 class="home-section-title">System Flow</h2>

                    <div class="system-flow-grid">
                        <div class="system-flow-step">1. Login / Register</div>
                        <div class="system-flow-step">2. Input Dataset</div>
                        <div class="system-flow-step">3. Preview Dataset</div>
                        <div class="system-flow-step">4. Train Model</div>
                        <div class="system-flow-step">5. View Training Result</div>
                        <div class="system-flow-step">6. Manage Trained Models</div>
                        <div class="system-flow-step">7. Select Model</div>
                        <div class="system-flow-step">8. Enter Future Date</div>
                        <div class="system-flow-step">9. Generate Prediction</div>
                        <div class="system-flow-step">10. View Prediction Result</div>
                    </div>
                </div>

                <div class="home-info-card scroll-fade-card">
                    <h2 class="home-section-title">Main Features</h2>

                    <ul class="home-feature-list">
                        <li>Automatic dataset fetching by preset or ticker symbol</li>
                        <li>Manual CSV upload as a fallback option</li>
                        <li>LSTM, Linear Regression, and Moving Average model comparison</li>
                        <li>Training chart and RMSE-based performance evaluation</li>
                        <li>Date-based future stock price prediction</li>
                        <li>Percentage change and market trend interpretation</li>
                        <li>Prediction result export to TXT format</li>
                    </ul>
                </div>
            </div>

        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cards = document.querySelectorAll('.scroll-fade-card');
        const animationKey = 'homeInfoFadeUpPlayed';

        if (!cards.length) return;

        if (sessionStorage.getItem(animationKey) === 'true') {
            cards.forEach(function (card) {
                card.classList.add('fade-up-done');
            });

            return;
        }

        const observer = new IntersectionObserver(function (entries, observerInstance) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    cards.forEach(function (card, index) {
                        setTimeout(function () {
                            card.classList.add('fade-up-visible');
                        }, index * 160);
                    });

                    sessionStorage.setItem(animationKey, 'true');
                    observerInstance.disconnect();
                }
            });
        }, {
            threshold: 0.18
        });

        cards.forEach(function (card) {
            observer.observe(card);
        });
    });
</script>
@endsection