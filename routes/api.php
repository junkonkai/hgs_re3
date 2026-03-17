<?php

use App\Http\Controllers\Api\GameMakerController;
use App\Http\Controllers\Api\Test\AccountController;
use App\Http\Controllers\Api\Test\FearMeterController;
use App\Http\Controllers\Api\UserFavoriteController;
use Illuminate\Support\Facades\Route;


if (!app()->environment('production')) {
    Route::get('test/registration-url', [AccountController::class, 'getRegistrationUrlForTest'])->name('api.test.registration-url');
    Route::get('test/password-reset-url', [AccountController::class, 'getPasswordResetUrlForTest'])->name('api.test.password-reset-url');
    Route::get('test/email-change-url', [AccountController::class, 'getEmailChangeUrlForTest'])->name('api.test.email-change-url');
    Route::post('test/expire-registration', [AccountController::class, 'expireRegistrationForTest'])->name('api.test.expire-registration');
    Route::post('test/reset-webmaster-password', [AccountController::class, 'resetWebmasterPasswordForTest'])->name('api.test.reset-webmaster-password');
    Route::post('test/create-test-account', [AccountController::class, 'createTestAccount'])->name('api.test.create-test-account');
    Route::post('test/fear-meter/recalculate', [FearMeterController::class, 'recalculate'])->name('api.test.fear-meter.recalculate');
    Route::get('test/fear-meter/statistics', [FearMeterController::class, 'statistics'])->name('api.test.fear-meter.statistics');
}

// ゲーム関連API
Route::get('game/maker/suggest', [GameMakerController::class, 'suggest'])->name('api.game.maker.suggest');

// 認証が必要なAPI
Route::middleware(['web', 'auth:web'])->group(function () {
    Route::post('user/favorite/toggle', [UserFavoriteController::class, 'toggle'])->name('api.user.favorite.toggle');
});


