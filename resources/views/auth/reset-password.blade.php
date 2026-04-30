@extends('layouts.guest')

@section('content')
<div class="auth-page">
    <div class="auth-left">
        <div class="auth-brand">SMPP</div>

        <div class="auth-left-content">
            <h1>
                Create<br>
                New<br>
                Password
            </h1>

            <p>
                Enter your new<br>
                account password.
            </p>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-card">
            <h2 class="auth-form-title">Reset Password</h2>
            <br>
            <p class="auth-description">
                Enter your email address and new password to reset your account login.
            </p>
            <br>
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

            <form method="POST" action="{{ route('password.store') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="auth-field">
                    <label for="email">Email Address</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $request->email) }}"
                        required
                        autofocus
                        autocomplete="username"
                    >

                    @error('email')
                        <div class="auth-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="password">New Password</label>
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
                    <label for="password_confirmation">Confirm New Password</label>
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

                <button type="submit" class="auth-submit-btn">
                    Reset Password
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