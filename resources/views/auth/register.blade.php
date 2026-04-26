@extends('layouts.guest')

@section('content')
<div class="auth-page">
    <div class="auth-left">
        <div class="auth-brand">SMPP</div>

        <div class="auth-left-content">
            <h1>
                AI-Powered<br>
                Stock<br>
                Predictions
            </h1>

            <p>
                Make your<br>
                predictions now.
            </p>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-card">
            <div class="social-row">
                <button type="button" class="social-btn">
                    Sign up with Google
                    <img src="{{ asset('icons/Google_icon.png') }}" alt="Google Icon" class="social-icon google-icon">
                </button>

                <button type="button" class="social-btn">
                    Sign in with Facebook
                    <img src="{{ asset('icons/Facebook_icon.png') }}" alt="Facebook Icon" class="social-icon facebook-icon">
                </button>
            </div>

            <div class="auth-divider">
                <span>or</span>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="auth-name-row">
                    <div class="auth-field">
                        <label for="first_name">First Name</label>
                        <input
                            id="first_name"
                            type="text"
                            name="first_name"
                            value="{{ old('first_name') }}"
                            required
                            autofocus
                            autocomplete="given-name"
                        >

                        @error('first_name')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="last_name">Last Name</label>
                        <input
                            id="last_name"
                            type="text"
                            name="last_name"
                            value="{{ old('last_name') }}"
                            required
                            autocomplete="family-name"
                        >

                        @error('last_name')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="auth-field">
                    <label for="email">Email Address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="username"
                    >

                    @error('email')
                        <div class="auth-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                    >

                    <small>At least 8 characters, with numbers and symbols</small>

                    @error('password')
                        <div class="auth-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="password_confirmation">Confirm Password</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                    >

                    @error('password_confirmation')
                        <div class="auth-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="terms-row">
                    <input
                        type="checkbox"
                        id="termsCheckbox"
                        class="terms-checkbox"
                        disabled
                    >

                    <input
                        type="hidden"
                        name="terms_accepted"
                        id="termsAccepted"
                        value="0"
                    >

                    <span>
                        I have read and agree to the
                        <button type="button" id="openTermsBtn" class="terms-link">
                            Terms and Conditions
                        </button>
                    </span>
                </div>

                @error('terms_accepted')
                    <div class="auth-error">{{ $message }}</div>
                @enderror

                <button type="submit" id="createAccountBtn" class="auth-submit-btn" disabled>
                    Create Account
                </button>

                <p class="auth-footer-text">
                    By creating an account, you agree to use this system responsibly.
                </p>
            </form>

            <p class="auth-switch-text">
                Already have an account?
                <a href="{{ route('login') }}">Sign in</a>
            </p>
        </div>
    </div>
</div>

<div id="termsModal" class="terms-modal-overlay" style="display: none;">
    <div class="terms-modal">
        <div class="terms-modal-header">
            <h2>Terms and Conditions</h2>
            <button type="button" id="closeTermsBtn" class="terms-close-btn">
                ×
            </button>
        </div>

        <div id="termsContent" class="terms-modal-content">
            <h3>Use of SMPP</h3>
            <p>
                SMPP is a web-based stock market price prediction system developed for academic and learning purposes.
                The system uses historical stock market data and machine learning models to generate prediction results.
            </p>

            <h3>Prediction Disclaimer</h3>
            <p>
                The prediction results generated by this system are estimates based on available historical data.
                They should not be treated as guaranteed financial advice or investment recommendations.
            </p>

            <h3>User Responsibility</h3>
            <p>
                Users are responsible for the datasets they upload, the prediction dates they select, and how they interpret
                the generated results. Any decisions made based on the prediction output are fully the user’s responsibility.
            </p>

            <h3>Account Usage</h3>
            <p>
                Users should provide accurate registration details and keep their login information secure.
                Users should not share their account password with others.
            </p>

            <h3>Dataset Upload</h3>
            <p>
                Users may upload CSV datasets for training and prediction. Uploaded datasets should follow the required
                format stated in the system. Invalid, incomplete, or wrongly formatted datasets may cause training or
                prediction errors.
            </p>

            <h3>Data Privacy</h3>
            <p>
                The system stores user account details, uploaded dataset records, trained model records, and prediction
                history for system functionality. The information is used only to support the features of the system.
            </p>

            <h3>System Limitation</h3>
            <p>
                Machine learning models may produce different results depending on dataset quality, date range, model
                settings, and training behavior. The system does not guarantee perfect prediction accuracy.
            </p>

            <h3>Educational Purpose</h3>
            <p>
                This system is intended for final year project demonstration, learning, and experimentation. It should not
                be used as the only basis for real stock trading or financial decisions.
            </p>

            <h3>Agreement</h3>
            <p>
                By clicking the Agree button, you confirm that you have read and understood these Terms and Conditions.
                You also agree to use SMPP responsibly and understand that the prediction results are for educational
                reference only.
            </p>
        </div>

        <div class="terms-modal-footer">
            <button type="button" id="agreeTermsBtn" class="terms-agree-btn" disabled>
                Agree
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const openTermsBtn = document.getElementById('openTermsBtn');
        const closeTermsBtn = document.getElementById('closeTermsBtn');
        const termsModal = document.getElementById('termsModal');
        const termsContent = document.getElementById('termsContent');
        const agreeTermsBtn = document.getElementById('agreeTermsBtn');
        const termsCheckbox = document.getElementById('termsCheckbox');
        const termsAccepted = document.getElementById('termsAccepted');
        const createAccountBtn = document.getElementById('createAccountBtn');

        function openModal() {
            termsModal.style.display = 'flex';

            agreeTermsBtn.disabled = true;
            agreeTermsBtn.classList.remove('active');

            termsContent.scrollTop = 0;
        }

        function closeModal() {
            termsModal.style.display = 'none';
        }

        if (openTermsBtn) {
            openTermsBtn.addEventListener('click', function () {
                openModal();
            });
        }

        if (closeTermsBtn) {
            closeTermsBtn.addEventListener('click', function () {
                closeModal();
            });
        }

        if (termsModal) {
            termsModal.addEventListener('click', function (e) {
                if (e.target === termsModal) {
                    closeModal();
                }
            });
        }

        if (termsContent) {
            termsContent.addEventListener('scroll', function () {
                const scrolledToBottom =
                    termsContent.scrollTop + termsContent.clientHeight >= termsContent.scrollHeight - 5;

                if (scrolledToBottom) {
                    agreeTermsBtn.disabled = false;
                    agreeTermsBtn.classList.add('active');
                }
            });
        }

        if (agreeTermsBtn) {
            agreeTermsBtn.addEventListener('click', function () {
                if (agreeTermsBtn.disabled) return;

                termsCheckbox.checked = true;
                termsAccepted.value = '1';

                createAccountBtn.disabled = false;
                createAccountBtn.classList.add('active');

                closeModal();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    });
</script>
@endsection