<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\GuidePageController;
use App\Http\Controllers\Doctor\DashboardController as DoctorDashboard;
use App\Http\Controllers\Doctor\ChatController;
use App\Http\Controllers\Doctor\FileController as DoctorFileController;
use App\Http\Controllers\Doctor\SubscriptionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\UserSubscriptionController;
use App\Http\Controllers\Admin\FileController as AdminFileController;
use App\Http\Controllers\Admin\GuideController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Payment\PaymobController;
use App\Http\Controllers\Payment\WebhookController;
use Illuminate\Support\Facades\Route;

// ─── Public Routes ───────────────────────────────────

Route::get('/', [LandingController::class, 'index'])
    ->middleware('browser.cache:public')
    ->name('landing');
Route::get('/guide', [GuidePageController::class, 'index'])
    ->middleware('browser.cache:guide')
    ->name('guide.index');
Route::get('/guide/{slug}', [GuidePageController::class, 'show'])
    ->middleware('browser.cache:guide')
    ->name('guide.show');

// ─── Profile Routes ─────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─── Auth Routes ─────────────────────────────────────

require __DIR__.'/auth.php';

// ─── Doctor Routes ───────────────────────────────────

// Routes that DON'T need active subscription (plans must be accessible)
Route::middleware(['auth', 'doctor', 'verified'])
    ->prefix('doctor')
    ->name('doctor.')
    ->group(function () {

    // Subscription Plans (no subscription required to view/subscribe)
    Route::get('/plans', [SubscriptionController::class, 'plans'])->name('plans');
    Route::post('/subscribe/{plan}', [SubscriptionController::class, 'subscribe'])->name('subscribe');
});

// Routes that REQUIRE active subscription
Route::middleware(['auth', 'doctor', 'verified', 'active.subscription'])
    ->prefix('doctor')
    ->name('doctor.')
    ->group(function () {

    // Dashboard
    Route::get('/dashboard', [DoctorDashboard::class, 'index'])->name('dashboard');

    // File Management
    Route::get('/files', [DoctorFileController::class, 'index'])->name('files.index');
    Route::post('/files/upload', [DoctorFileController::class, 'upload'])->name('files.upload');
    Route::get('/files/{file}/progress', [DoctorFileController::class, 'progress'])->name('files.progress');
    Route::get('/files/{file}/metrics', [DoctorFileController::class, 'metrics'])->name('files.metrics');
    Route::delete('/files/{file}', [DoctorFileController::class, 'destroy'])->name('files.destroy');

    // Chat
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::get('/chat/{session}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{session}/rename', [ChatController::class, 'rename'])->name('chat.rename');
    Route::delete('/chat/{session}', [ChatController::class, 'destroy'])->name('chat.destroy');
});

// ─── Admin Routes ────────────────────────────────────

Route::middleware(['auth', 'admin', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

    // Users Management
    Route::resource('users', UserController::class);

    // Subscription Plans
    Route::resource('plans', SubscriptionPlanController::class);

    // User Subscriptions
    Route::get('/subscriptions', [UserSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/assign', [UserSubscriptionController::class, 'create'])->name('subscriptions.create');
    Route::post('/subscriptions/assign', [UserSubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::post('/subscriptions/{user}/assign', [UserSubscriptionController::class, 'assign'])->name('subscriptions.assign');

    // Files
    Route::get('/files', [AdminFileController::class, 'index'])->name('files.index');
    Route::delete('/files/{file}', [AdminFileController::class, 'destroy'])->name('files.destroy');

    // Guide Management
    Route::resource('guide', GuideController::class);

    // Statistics
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics');

    // Admin Chat
    Route::get('/chat', [AdminChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [AdminChatController::class, 'sendMessage'])->name('chat.send');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

// ─── Payment Webhooks ────────────────────────────────

Route::post('/paymob/webhook', [WebhookController::class, 'handle'])->name('paymob.webhook');
Route::get('/payment/callback', [PaymobController::class, 'callback'])->name('payment.callback');
Route::get('/payment/success', [PaymobController::class, 'success'])->name('payment.success');
Route::get('/payment/fail', [PaymobController::class, 'fail'])->name('payment.fail');
