<?php

use App\Http\Controllers\Api\GameMakerController;
use App\Http\Controllers\Api\Admin\Game\MakerController as AdminGameMakerController;
use App\Http\Controllers\Api\Admin\Game\FranchiseController as AdminGameFranchiseController;
use App\Http\Controllers\Api\Admin\Game\PlatformController as AdminGamePlatformController;
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

// ゲームマスター管理API（v1）
Route::prefix('v1/admin/game')->group(function ()
{
    Route::get('makers', [AdminGameMakerController::class, 'index'])->name('api.v1.admin.game.makers.index');
    Route::post('makers', [AdminGameMakerController::class, 'store'])->name('api.v1.admin.game.makers.store');
    Route::get('makers/{id}', [AdminGameMakerController::class, 'show'])->name('api.v1.admin.game.makers.show');
    Route::put('makers/{id}', [AdminGameMakerController::class, 'update'])->name('api.v1.admin.game.makers.update');
    Route::delete('makers/{id}', [AdminGameMakerController::class, 'destroy'])->name('api.v1.admin.game.makers.destroy');

    Route::get('makers/{id}/packages', [AdminGameMakerController::class, 'packagesIndex'])->name('api.v1.admin.game.makers.packages.index');
    Route::post('makers/{id}/packages', [AdminGameMakerController::class, 'packagesAttach'])->name('api.v1.admin.game.makers.packages.attach');
    Route::put('makers/{id}/packages', [AdminGameMakerController::class, 'packagesSync'])->name('api.v1.admin.game.makers.packages.sync');
    Route::delete('makers/{id}/packages/{packageId}', [AdminGameMakerController::class, 'packagesDetach'])->name('api.v1.admin.game.makers.packages.detach');

    Route::get('platforms', [AdminGamePlatformController::class, 'index'])->name('api.v1.admin.game.platforms.index');
    Route::post('platforms', [AdminGamePlatformController::class, 'store'])->name('api.v1.admin.game.platforms.store');
    Route::get('platforms/{id}', [AdminGamePlatformController::class, 'show'])->name('api.v1.admin.game.platforms.show');
    Route::put('platforms/{id}', [AdminGamePlatformController::class, 'update'])->name('api.v1.admin.game.platforms.update');
    Route::delete('platforms/{id}', [AdminGamePlatformController::class, 'destroy'])->name('api.v1.admin.game.platforms.destroy');

    Route::get('platforms/{id}/related-products', [AdminGamePlatformController::class, 'relatedProductsIndex'])->name('api.v1.admin.game.platforms.related-products.index');
    Route::post('platforms/{id}/related-products', [AdminGamePlatformController::class, 'relatedProductsAttach'])->name('api.v1.admin.game.platforms.related-products.attach');
    Route::put('platforms/{id}/related-products', [AdminGamePlatformController::class, 'relatedProductsSync'])->name('api.v1.admin.game.platforms.related-products.sync');
    Route::delete('platforms/{id}/related-products/{relatedProductId}', [AdminGamePlatformController::class, 'relatedProductsDetach'])->name('api.v1.admin.game.platforms.related-products.detach');

    Route::get('franchises', [AdminGameFranchiseController::class, 'index'])->name('api.v1.admin.game.franchises.index');
    Route::post('franchises', [AdminGameFranchiseController::class, 'store'])->name('api.v1.admin.game.franchises.store');
    Route::get('franchises/{id}', [AdminGameFranchiseController::class, 'show'])->name('api.v1.admin.game.franchises.show');
    Route::put('franchises/{id}', [AdminGameFranchiseController::class, 'update'])->name('api.v1.admin.game.franchises.update');
    Route::delete('franchises/{id}', [AdminGameFranchiseController::class, 'destroy'])->name('api.v1.admin.game.franchises.destroy');

    Route::get('franchises/{id}/series', [AdminGameFranchiseController::class, 'seriesIndex'])->name('api.v1.admin.game.franchises.series.index');
    Route::put('franchises/{id}/series', [AdminGameFranchiseController::class, 'seriesSync'])->name('api.v1.admin.game.franchises.series.sync');
    Route::delete('franchises/{id}/series/{seriesId}', [AdminGameFranchiseController::class, 'seriesDetach'])->name('api.v1.admin.game.franchises.series.detach');

    Route::get('franchises/{id}/titles', [AdminGameFranchiseController::class, 'titlesIndex'])->name('api.v1.admin.game.franchises.titles.index');
    Route::put('franchises/{id}/titles', [AdminGameFranchiseController::class, 'titlesSync'])->name('api.v1.admin.game.franchises.titles.sync');
    Route::delete('franchises/{id}/titles/{titleId}', [AdminGameFranchiseController::class, 'titlesDetach'])->name('api.v1.admin.game.franchises.titles.detach');
});

// 認証が必要なAPI
Route::middleware(['web', 'auth:web'])->group(function () {
    Route::post('user/favorite/toggle', [UserFavoriteController::class, 'toggle'])->name('api.user.favorite.toggle');
});


