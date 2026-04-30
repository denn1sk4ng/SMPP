@extends('layouts.guest')

@section('content')
<div class="auth-page">
    <div class="auth-left">
        <div class="auth-brand">SMPP</div>

        <div class="auth-left-content">
            <h1>
                Reset<br>
                Your<br>
                Password
            </h1>

            <p>
                Enter your email<br>
                to receive a reset link.
            </p>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-card">
            <h2 class="auth-form-title">Forgot Password</h2>
            <br>
            <p class="auth-description">
                Enter your registered email address and we will send you a password reset link.
            </p>
            <br>
            @if (session('status'))
                <div class="auth-success-box">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="auth-error-box">
                    <strong>Please check your input:</strong>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="auth-field">
                    <label for="email">Email Address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                    >

                    @error('email')
                        <div class="auth-error">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="auth-submit-btn">
                    Send Reset Link
                </button>
            </form>

            <p class="auth-switch-text">
                Remember your password?
                <a href="{{ route('login') }}">Back to Login</a>
            </p>
        </div>
    </div>
</div>
@endsection