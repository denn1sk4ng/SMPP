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
                    Sign in with Google
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

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="auth-field">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                    @error('email')
                        <div class="auth-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                    @error('password')
                        <div class="auth-error">{{ $message }}</div>
                    @enderror
                    @if (Route::has('password.request'))
                        <div class="forgot-password-row">
                            <a href="{{ route('password.request') }}">
                                Forgot password?
                            </a>
                        </div>
                    @endif
                </div>

                <label class="remember-row">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>

                <button type="submit" class="auth-submit-btn">
                    Sign In
                </button>

                <p class="auth-footer-text">
                    By logging in, you agree to follow our
                    <a href="#">terms of service</a>
                </p>
            </form>

            <p class="auth-switch-text">
                Don’t have an account?
                <a href="{{ route('register') }}">Create one</a>
            </p>
        </div>
    </div>
</div>
@endsection