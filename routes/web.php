<?php

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/datasets/upload', [DatasetController::class, 'create'])->name('datasets.create');
    Route::post('/datasets/upload', [DatasetController::class, 'store'])->name('datasets.store');
    Route::post('/datasets/fetch', [DatasetController::class, 'fetch'])->name('datasets.fetch');
    Route::get('/datasets/{id}/preview', [DatasetController::class, 'preview'])->whereNumber('id')->name('datasets.preview');
    Route::get('/datasets/{id}/download', [DatasetController::class, 'download'])->whereNumber('id')->name('datasets.download');

    Route::get('/models', [ModelController::class, 'index'])->name('models.index');
    Route::post('/models/train/{dataset}', [ModelController::class, 'train'])->whereNumber('dataset')->name('models.train');
    Route::get('/models/{id}/training-result', [ModelController::class, 'trainingResult'])->whereNumber('id')->name('models.trainingResult');
    Route::get('/models/{id}/chart', [ModelController::class, 'chart'])->whereNumber('id')->name('models.chart');
    Route::delete('/models/{id}', [ModelController::class, 'destroy'])->whereNumber('id')->name('models.destroy');

    Route::get('/predictions', [PredictionController::class, 'index'])->name('predictions.index');
    Route::get('/predictions/create/{model}', [PredictionController::class, 'create'])->whereNumber('model')->name('predictions.create');
    Route::post('/predictions/generate/{model}', [PredictionController::class, 'generate'])->whereNumber('model')->name('predictions.generate');
    Route::get('/predictions/{id}', [PredictionController::class, 'show'])->whereNumber('id')->name('predictions.show');
    Route::get('/predictions/{id}/export-txt', [PredictionController::class, 'exportTxt'])->whereNumber('id')->name('predictions.exportTxt');
    Route::get('/predictions/{id}/done', [PredictionController::class, 'done'])->whereNumber('id')->name('predictions.done');
    Route::delete('/predictions/{id}', [PredictionController::class, 'destroy'])->whereNumber('id')->name('predictions.destroy');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->middleware('guest')->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('guest')->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->middleware('guest')->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->middleware('guest')->name('password.store');

    Route::get('verify-email', EmailVerificationPromptController::class)->middleware('auth')->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['auth', 'signed', 'throttle:1,1'])->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');
});

Route::middleware('auth:sanctum')->get('/dashboard/summary', [DashboardApiController::class, 'summary'])->name('dashboard.summary');

require __DIR__ . '/auth.php';