@extends('layouts.guest')

@section('content')
<div class="auth-page">
    <div class="auth-left">
        <div class="auth-brand">SMPP</div>

        <div class="auth-left-content">
            <h1>
                Confirm<br>
                Your<br>
                Email
            </h1>

            <p>
                Verify your email<br>
                to continue.
            </p>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-card">

            @if (session('status'))
            <div class="auth-success-box">
                {{ session('status') }}
            </div>
            @endif

            <h2 class="auth-form-title">Confirm Email Address</h2>
            <br>
            <p class="auth-description">
                Thanks for registering. Before continuing, please verify your email address by clicking the verification link sent to your email.
            </p>
            <br>
            
            @if (session('status'))
                <div class="auth-success-box">
                    @if(session('status') == 'verification-link-sent')
                        A new verification link has been sent to your email address.
                    @else
                        {{ session('status') }}
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('verification.send') }}" id="resendVerificationForm">
                @csrf

                <button type="submit" class="auth-submit-btn" id="resendVerificationBtn">
                    Resend Verification Email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" style="margin-top: 14px;">
                @csrf

                <button type="submit" class="auth-secondary-submit-btn">
                    Cancel
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const resendForm = document.getElementById('resendVerificationForm');
    const resendBtn = document.getElementById('resendVerificationBtn');

    if (!resendForm || !resendBtn) return;

    const cooldownSeconds = 60;
    const storageKey = 'verificationResendCooldownEnd';

    function startCooldown(seconds) {
        const endTime = Date.now() + seconds * 1000;
        sessionStorage.setItem(storageKey, endTime.toString());
        runCooldown();
    }

    function runCooldown() {
        const endTime = parseInt(sessionStorage.getItem(storageKey), 10);

        if (!endTime || Date.now() >= endTime) {
            sessionStorage.removeItem(storageKey);
            resendBtn.disabled = false;
            resendBtn.textContent = 'Resend Verification Email';
            resendBtn.classList.remove('auth-btn-disabled');
            return;
        }

        resendBtn.disabled = true;
        resendBtn.classList.add('auth-btn-disabled');

        const timer = setInterval(function () {
            const remaining = Math.ceil((endTime - Date.now()) / 1000);

            if (remaining <= 0) {
                clearInterval(timer);
                sessionStorage.removeItem(storageKey);
                resendBtn.disabled = false;
                resendBtn.textContent = 'Resend Verification Email';
                resendBtn.classList.remove('auth-btn-disabled');
                return;
            }

            resendBtn.textContent = 'Resend available in ' + remaining + 's';
        }, 1000);
    }

    resendForm.addEventListener('submit', function () {
        startCooldown(cooldownSeconds);
    });

    runCooldown();
});
</script>
@endsection