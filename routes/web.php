<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\OfferController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\AiModelController;
use App\Http\Controllers\Admin\CrawlerController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AiContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\Admin\SubmissionController;
use App\Http\Controllers\Admin\ModelCreatorController;
use App\Http\Controllers\Admin\VerificationMethodController;

Route::get('/', [HomeController::class, 'index'])->name('public.home');
Route::get('offers/{slug}', [HomeController::class, 'show'])->name('public.offers.show');
Route::get('go/{slug}', [HomeController::class, 'go'])->name('public.go');
Route::get('ads/{id}/click', [HomeController::class, 'adClick'])->name('public.ads.click')->whereNumber('id');
Route::get('sitemap.xml', [HomeController::class, 'sitemap'])->name('public.sitemap');
Route::get('locale/{lang}', [LocaleController::class, 'switch'])->name('public.locale.switch')->where('lang', 'fa|en');

Route::post('offers/{slug}/report', [HomeController::class, 'report'])
    ->name('public.offers.report')
    ->middleware('throttle:10,1');

Route::post('submit', [HomeController::class, 'submit'])
    ->name('public.submit')
    ->middleware('throttle:10,1');

Route::post('analytics/event', [HomeController::class, 'analyticsEvent'])
    ->name('public.analytics.event')
    ->middleware('throttle:60,1');

// Telegram bot webhook (OpenAI-compatible pipeline: incoming post -> AI -> Offer)
Route::post('telegram/webhook', [TelegramWebhookController::class, 'webhook'])
    ->name('public.telegram.webhook')
    ->middleware('throttle:30,1');
Route::get('telegram/register-webhook', [TelegramWebhookController::class, 'registerWebhook'])
    ->name('public.telegram.register')
    ->middleware('auth.admin');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.attempt')->middleware('throttle:5,1');

    Route::middleware('auth.admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // Full CRUD resources (controller handles its own ACL).
        $crud = function (string $prefix, string $controller, string $name): void {
            Route::get($prefix, [$controller, 'index'])->name($name.'.index');
            Route::get($prefix.'/create', [$controller, 'create'])->name($name.'.create');
            Route::post($prefix, [$controller, 'store'])->name($name.'.store');
            Route::get($prefix.'/{id}/edit', [$controller, 'edit'])->name($name.'.edit')->whereNumber('id');
            Route::match(['put', 'patch'], $prefix.'/{id}', [$controller, 'update'])->name($name.'.update')->whereNumber('id');
            Route::delete($prefix.'/{id}', [$controller, 'destroy'])->name($name.'.destroy')->whereNumber('id');
        };

        $crud('providers', ProviderController::class, 'providers');
        $crud('categories', CategoryController::class, 'categories');
        $crud('ai-models', AiModelController::class, 'ai-models');
        $crud('model-creators', ModelCreatorController::class, 'model-creators');
        $crud('tags', TagController::class, 'tags');
        $crud('verification-methods', VerificationMethodController::class, 'verification-methods');
        $crud('admins', AdminUserController::class, 'admins');
        $crud('ads', AdController::class, 'ads');

        // Offers + extras
        $crud('offers', OfferController::class, 'offers');
        Route::post('offers/bulk', [OfferController::class, 'bulk'])->name('offers.bulk');
        Route::post('offers/{id}/overrides', [OfferController::class, 'storeOverride'])->name('offers.overrides.store')->whereNumber('id');
        Route::delete('offers/{id}/overrides/{overrideId}', [OfferController::class, 'destroyOverride'])->name('offers.overrides.destroy')->whereNumber('id')->whereNumber('overrideId');

        // Moderation
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('reports/{id}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve')->whereNumber('id');
        Route::post('reports/{id}/reject', [ReportController::class, 'reject'])->name('reports.reject')->whereNumber('id');
        Route::get('submissions', [SubmissionController::class, 'index'])->name('submissions.index');
        Route::post('submissions/{id}/approve', [SubmissionController::class, 'approve'])->name('submissions.approve')->whereNumber('id');
        Route::post('submissions/{id}/reject', [SubmissionController::class, 'reject'])->name('submissions.reject')->whereNumber('id');

        // Settings + profile + crawler placeholder
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::match(['put', 'patch'], 'settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::match(['put', 'patch'], 'profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('crawler', [CrawlerController::class, 'index'])->name('crawler.index');

        // Analytics / Reports
        Route::get('analytics', [ReportsController::class, 'index'])->name('analytics.index');
        Route::get('analytics/export', [ReportsController::class, 'export'])->name('analytics.export');

        // Telegram + AI content pipeline
        Route::get('ai-content', [AiContentController::class, 'index'])->name('ai-content.index');
        Route::get('ai-content/{id}', [AiContentController::class, 'show'])->name('ai-content.show')->whereNumber('id');
        Route::post('ai-content/{id}/retry', [AiContentController::class, 'retry'])->name('ai-content.retry')->whereNumber('id');
        Route::get('ai-content/settings', [AiContentController::class, 'settings'])->name('ai-content.settings');
        Route::match(['put', 'patch'], 'ai-content/settings', [AiContentController::class, 'updateSettings'])->name('ai-content.settings.update');
        Route::post('ai-content/test-llm', [AiContentController::class, 'testLlm'])->name('ai-content.test-llm');
        Route::post('ai-content/register-webhook', [AiContentController::class, 'registerWebhook'])->name('ai-content.register-webhook');
    });
});
