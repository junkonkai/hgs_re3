<?php

use App\Http\Controllers\Api\GameMakerController;
use App\Http\Controllers\Api\Admin\Game\MakerController as AdminGameMakerController;
use App\Http\Controllers\Api\Admin\Game\FranchiseController as AdminGameFranchiseController;
use App\Http\Controllers\Api\Admin\Game\SeriesController as AdminGameSeriesController;
use App\Http\Controllers\Api\Admin\Game\TitleController as AdminGameTitleController;
use App\Http\Controllers\Api\Admin\Game\PlatformController as AdminGamePlatformController;
use App\Http\Controllers\Api\Admin\Game\PackageGroupController as AdminGamePackageGroupController;
use App\Http\Controllers\Api\Admin\Game\PackageController as AdminGamePackageController;
use App\Http\Controllers\Api\Admin\Game\RelatedProductController as AdminGameRelatedProductController;
use App\Http\Controllers\Api\Admin\Game\MediaMixController as AdminGameMediaMixController;
use App\Http\Controllers\Api\Admin\Game\MediaMixGroupController as AdminGameMediaMixGroupController;
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
    Route::post('franchises/{id}/series', [AdminGameFranchiseController::class, 'seriesAttach'])->name('api.v1.admin.game.franchises.series.attach');
    Route::put('franchises/{id}/series', [AdminGameFranchiseController::class, 'seriesSync'])->name('api.v1.admin.game.franchises.series.sync');
    Route::delete('franchises/{id}/series/{seriesId}', [AdminGameFranchiseController::class, 'seriesDetach'])->name('api.v1.admin.game.franchises.series.detach');

    Route::get('franchises/{id}/titles', [AdminGameFranchiseController::class, 'titlesIndex'])->name('api.v1.admin.game.franchises.titles.index');
    Route::post('franchises/{id}/titles', [AdminGameFranchiseController::class, 'titlesAttach'])->name('api.v1.admin.game.franchises.titles.attach');
    Route::put('franchises/{id}/titles', [AdminGameFranchiseController::class, 'titlesSync'])->name('api.v1.admin.game.franchises.titles.sync');
    Route::delete('franchises/{id}/titles/{titleId}', [AdminGameFranchiseController::class, 'titlesDetach'])->name('api.v1.admin.game.franchises.titles.detach');

    Route::get('series', [AdminGameSeriesController::class, 'index'])->name('api.v1.admin.game.series.index');
    Route::post('series', [AdminGameSeriesController::class, 'store'])->name('api.v1.admin.game.series.store');
    Route::get('series/{id}', [AdminGameSeriesController::class, 'show'])->name('api.v1.admin.game.series.show');
    Route::put('series/{id}', [AdminGameSeriesController::class, 'update'])->name('api.v1.admin.game.series.update');
    Route::delete('series/{id}', [AdminGameSeriesController::class, 'destroy'])->name('api.v1.admin.game.series.destroy');
    Route::get('series/{id}/titles', [AdminGameSeriesController::class, 'titlesIndex'])->name('api.v1.admin.game.series.titles.index');
    Route::post('series/{id}/titles', [AdminGameSeriesController::class, 'titlesAttach'])->name('api.v1.admin.game.series.titles.attach');
    Route::put('series/{id}/titles', [AdminGameSeriesController::class, 'titlesSync'])->name('api.v1.admin.game.series.titles.sync');
    Route::delete('series/{id}/titles/{titleId}', [AdminGameSeriesController::class, 'titlesDetach'])->name('api.v1.admin.game.series.titles.detach');

    Route::get('titles', [AdminGameTitleController::class, 'index'])->name('api.v1.admin.game.titles.index');
    Route::post('titles', [AdminGameTitleController::class, 'store'])->name('api.v1.admin.game.titles.store');
    Route::get('titles/{id}', [AdminGameTitleController::class, 'show'])->name('api.v1.admin.game.titles.show');
    Route::put('titles/{id}', [AdminGameTitleController::class, 'update'])->name('api.v1.admin.game.titles.update');
    Route::delete('titles/{id}', [AdminGameTitleController::class, 'destroy'])->name('api.v1.admin.game.titles.destroy');
    Route::get('titles/{id}/package-groups', [AdminGameTitleController::class, 'packageGroupsIndex'])->name('api.v1.admin.game.titles.package-groups.index');
    Route::post('titles/{id}/package-groups', [AdminGameTitleController::class, 'packageGroupsAttach'])->name('api.v1.admin.game.titles.package-groups.attach');
    Route::put('titles/{id}/package-groups', [AdminGameTitleController::class, 'packageGroupsSync'])->name('api.v1.admin.game.titles.package-groups.sync');
    Route::delete('titles/{id}/package-groups/{packageGroupId}', [AdminGameTitleController::class, 'packageGroupsDetach'])->name('api.v1.admin.game.titles.package-groups.detach');
    Route::get('titles/{id}/related-products', [AdminGameTitleController::class, 'relatedProductsIndex'])->name('api.v1.admin.game.titles.related-products.index');
    Route::post('titles/{id}/related-products', [AdminGameTitleController::class, 'relatedProductsAttach'])->name('api.v1.admin.game.titles.related-products.attach');
    Route::put('titles/{id}/related-products', [AdminGameTitleController::class, 'relatedProductsSync'])->name('api.v1.admin.game.titles.related-products.sync');
    Route::delete('titles/{id}/related-products/{relatedProductId}', [AdminGameTitleController::class, 'relatedProductsDetach'])->name('api.v1.admin.game.titles.related-products.detach');
    Route::get('titles/{id}/media-mixes', [AdminGameTitleController::class, 'mediaMixesIndex'])->name('api.v1.admin.game.titles.media-mixes.index');
    Route::post('titles/{id}/media-mixes', [AdminGameTitleController::class, 'mediaMixesAttach'])->name('api.v1.admin.game.titles.media-mixes.attach');
    Route::put('titles/{id}/media-mixes', [AdminGameTitleController::class, 'mediaMixesSync'])->name('api.v1.admin.game.titles.media-mixes.sync');
    Route::delete('titles/{id}/media-mixes/{mediaMixId}', [AdminGameTitleController::class, 'mediaMixesDetach'])->name('api.v1.admin.game.titles.media-mixes.detach');

    Route::get('package-groups', [AdminGamePackageGroupController::class, 'index'])->name('api.v1.admin.game.package-groups.index');
    Route::post('package-groups', [AdminGamePackageGroupController::class, 'store'])->name('api.v1.admin.game.package-groups.store');
    Route::get('package-groups/{id}', [AdminGamePackageGroupController::class, 'show'])->name('api.v1.admin.game.package-groups.show');
    Route::put('package-groups/{id}', [AdminGamePackageGroupController::class, 'update'])->name('api.v1.admin.game.package-groups.update');
    Route::delete('package-groups/{id}', [AdminGamePackageGroupController::class, 'destroy'])->name('api.v1.admin.game.package-groups.destroy');
    Route::get('package-groups/{id}/titles', [AdminGamePackageGroupController::class, 'titlesIndex'])->name('api.v1.admin.game.package-groups.titles.index');
    Route::post('package-groups/{id}/titles', [AdminGamePackageGroupController::class, 'titlesAttach'])->name('api.v1.admin.game.package-groups.titles.attach');
    Route::put('package-groups/{id}/titles', [AdminGamePackageGroupController::class, 'titlesSync'])->name('api.v1.admin.game.package-groups.titles.sync');
    Route::delete('package-groups/{id}/titles/{titleId}', [AdminGamePackageGroupController::class, 'titlesDetach'])->name('api.v1.admin.game.package-groups.titles.detach');
    Route::get('package-groups/{id}/packages', [AdminGamePackageGroupController::class, 'packagesIndex'])->name('api.v1.admin.game.package-groups.packages.index');
    Route::post('package-groups/{id}/packages', [AdminGamePackageGroupController::class, 'packagesAttach'])->name('api.v1.admin.game.package-groups.packages.attach');
    Route::put('package-groups/{id}/packages', [AdminGamePackageGroupController::class, 'packagesSync'])->name('api.v1.admin.game.package-groups.packages.sync');
    Route::delete('package-groups/{id}/packages/{packageId}', [AdminGamePackageGroupController::class, 'packagesDetach'])->name('api.v1.admin.game.package-groups.packages.detach');

    Route::get('packages', [AdminGamePackageController::class, 'index'])->name('api.v1.admin.game.packages.index');
    Route::post('packages', [AdminGamePackageController::class, 'store'])->name('api.v1.admin.game.packages.store');
    Route::get('packages/{id}', [AdminGamePackageController::class, 'show'])->name('api.v1.admin.game.packages.show');
    Route::put('packages/{id}', [AdminGamePackageController::class, 'update'])->name('api.v1.admin.game.packages.update');
    Route::delete('packages/{id}', [AdminGamePackageController::class, 'destroy'])->name('api.v1.admin.game.packages.destroy');
    Route::get('packages/{id}/makers', [AdminGamePackageController::class, 'makersIndex'])->name('api.v1.admin.game.packages.makers.index');
    Route::post('packages/{id}/makers', [AdminGamePackageController::class, 'makersAttach'])->name('api.v1.admin.game.packages.makers.attach');
    Route::put('packages/{id}/makers', [AdminGamePackageController::class, 'makersSync'])->name('api.v1.admin.game.packages.makers.sync');
    Route::delete('packages/{id}/makers/{makerId}', [AdminGamePackageController::class, 'makersDetach'])->name('api.v1.admin.game.packages.makers.detach');
    Route::get('packages/{id}/package-groups', [AdminGamePackageController::class, 'packageGroupsIndex'])->name('api.v1.admin.game.packages.package-groups.index');
    Route::post('packages/{id}/package-groups', [AdminGamePackageController::class, 'packageGroupsAttach'])->name('api.v1.admin.game.packages.package-groups.attach');
    Route::put('packages/{id}/package-groups', [AdminGamePackageController::class, 'packageGroupsSync'])->name('api.v1.admin.game.packages.package-groups.sync');
    Route::delete('packages/{id}/package-groups/{packageGroupId}', [AdminGamePackageController::class, 'packageGroupsDetach'])->name('api.v1.admin.game.packages.package-groups.detach');
    Route::get('packages/{id}/shops', [AdminGamePackageController::class, 'shopsIndex'])->name('api.v1.admin.game.packages.shops.index');
    Route::post('packages/{id}/shops', [AdminGamePackageController::class, 'shopsStore'])->name('api.v1.admin.game.packages.shops.store');
    Route::get('packages/{id}/shops/{shopId}', [AdminGamePackageController::class, 'shopsShow'])->name('api.v1.admin.game.packages.shops.show');
    Route::put('packages/{id}/shops/{shopId}', [AdminGamePackageController::class, 'shopsUpdate'])->name('api.v1.admin.game.packages.shops.update');
    Route::delete('packages/{id}/shops/{shopId}', [AdminGamePackageController::class, 'shopsDestroy'])->name('api.v1.admin.game.packages.shops.destroy');

    Route::get('related-products', [AdminGameRelatedProductController::class, 'index'])->name('api.v1.admin.game.related-products.index');
    Route::post('related-products', [AdminGameRelatedProductController::class, 'store'])->name('api.v1.admin.game.related-products.store');
    Route::get('related-products/{id}', [AdminGameRelatedProductController::class, 'show'])->name('api.v1.admin.game.related-products.show');
    Route::put('related-products/{id}', [AdminGameRelatedProductController::class, 'update'])->name('api.v1.admin.game.related-products.update');
    Route::delete('related-products/{id}', [AdminGameRelatedProductController::class, 'destroy'])->name('api.v1.admin.game.related-products.destroy');
    Route::get('related-products/{id}/platforms', [AdminGameRelatedProductController::class, 'platformsIndex'])->name('api.v1.admin.game.related-products.platforms.index');
    Route::post('related-products/{id}/platforms', [AdminGameRelatedProductController::class, 'platformsAttach'])->name('api.v1.admin.game.related-products.platforms.attach');
    Route::put('related-products/{id}/platforms', [AdminGameRelatedProductController::class, 'platformsSync'])->name('api.v1.admin.game.related-products.platforms.sync');
    Route::delete('related-products/{id}/platforms/{platformId}', [AdminGameRelatedProductController::class, 'platformsDetach'])->name('api.v1.admin.game.related-products.platforms.detach');
    Route::get('related-products/{id}/titles', [AdminGameRelatedProductController::class, 'titlesIndex'])->name('api.v1.admin.game.related-products.titles.index');
    Route::post('related-products/{id}/titles', [AdminGameRelatedProductController::class, 'titlesAttach'])->name('api.v1.admin.game.related-products.titles.attach');
    Route::put('related-products/{id}/titles', [AdminGameRelatedProductController::class, 'titlesSync'])->name('api.v1.admin.game.related-products.titles.sync');
    Route::delete('related-products/{id}/titles/{titleId}', [AdminGameRelatedProductController::class, 'titlesDetach'])->name('api.v1.admin.game.related-products.titles.detach');
    Route::get('related-products/{id}/media-mixes', [AdminGameRelatedProductController::class, 'mediaMixesIndex'])->name('api.v1.admin.game.related-products.media-mixes.index');
    Route::post('related-products/{id}/media-mixes', [AdminGameRelatedProductController::class, 'mediaMixesAttach'])->name('api.v1.admin.game.related-products.media-mixes.attach');
    Route::put('related-products/{id}/media-mixes', [AdminGameRelatedProductController::class, 'mediaMixesSync'])->name('api.v1.admin.game.related-products.media-mixes.sync');
    Route::delete('related-products/{id}/media-mixes/{mediaMixId}', [AdminGameRelatedProductController::class, 'mediaMixesDetach'])->name('api.v1.admin.game.related-products.media-mixes.detach');
    Route::get('related-products/{id}/shops', [AdminGameRelatedProductController::class, 'shopsIndex'])->name('api.v1.admin.game.related-products.shops.index');
    Route::post('related-products/{id}/shops', [AdminGameRelatedProductController::class, 'shopsStore'])->name('api.v1.admin.game.related-products.shops.store');
    Route::get('related-products/{id}/shops/{shopId}', [AdminGameRelatedProductController::class, 'shopsShow'])->name('api.v1.admin.game.related-products.shops.show');
    Route::put('related-products/{id}/shops/{shopId}', [AdminGameRelatedProductController::class, 'shopsUpdate'])->name('api.v1.admin.game.related-products.shops.update');
    Route::delete('related-products/{id}/shops/{shopId}', [AdminGameRelatedProductController::class, 'shopsDestroy'])->name('api.v1.admin.game.related-products.shops.destroy');

    Route::get('media-mix-groups', [AdminGameMediaMixGroupController::class, 'index'])->name('api.v1.admin.game.media-mix-groups.index');
    Route::post('media-mix-groups', [AdminGameMediaMixGroupController::class, 'store'])->name('api.v1.admin.game.media-mix-groups.store');
    Route::get('media-mix-groups/{id}', [AdminGameMediaMixGroupController::class, 'show'])->name('api.v1.admin.game.media-mix-groups.show');
    Route::put('media-mix-groups/{id}', [AdminGameMediaMixGroupController::class, 'update'])->name('api.v1.admin.game.media-mix-groups.update');
    Route::delete('media-mix-groups/{id}', [AdminGameMediaMixGroupController::class, 'destroy'])->name('api.v1.admin.game.media-mix-groups.destroy');
    Route::get('media-mix-groups/{id}/media-mixes', [AdminGameMediaMixGroupController::class, 'mediaMixesIndex'])->name('api.v1.admin.game.media-mix-groups.media-mixes.index');
    Route::post('media-mix-groups/{id}/media-mixes', [AdminGameMediaMixGroupController::class, 'mediaMixesAttach'])->name('api.v1.admin.game.media-mix-groups.media-mixes.attach');
    Route::put('media-mix-groups/{id}/media-mixes', [AdminGameMediaMixGroupController::class, 'mediaMixesSync'])->name('api.v1.admin.game.media-mix-groups.media-mixes.sync');
    Route::delete('media-mix-groups/{id}/media-mixes/{mediaMixId}', [AdminGameMediaMixGroupController::class, 'mediaMixesDetach'])->name('api.v1.admin.game.media-mix-groups.media-mixes.detach');

    Route::get('media-mixes', [AdminGameMediaMixController::class, 'index'])->name('api.v1.admin.game.media-mixes.index');
    Route::post('media-mixes', [AdminGameMediaMixController::class, 'store'])->name('api.v1.admin.game.media-mixes.store');
    Route::get('media-mixes/{id}', [AdminGameMediaMixController::class, 'show'])->name('api.v1.admin.game.media-mixes.show');
    Route::put('media-mixes/{id}', [AdminGameMediaMixController::class, 'update'])->name('api.v1.admin.game.media-mixes.update');
    Route::delete('media-mixes/{id}', [AdminGameMediaMixController::class, 'destroy'])->name('api.v1.admin.game.media-mixes.destroy');
    Route::get('media-mixes/{id}/titles', [AdminGameMediaMixController::class, 'titlesIndex'])->name('api.v1.admin.game.media-mixes.titles.index');
    Route::post('media-mixes/{id}/titles', [AdminGameMediaMixController::class, 'titlesAttach'])->name('api.v1.admin.game.media-mixes.titles.attach');
    Route::put('media-mixes/{id}/titles', [AdminGameMediaMixController::class, 'titlesSync'])->name('api.v1.admin.game.media-mixes.titles.sync');
    Route::delete('media-mixes/{id}/titles/{titleId}', [AdminGameMediaMixController::class, 'titlesDetach'])->name('api.v1.admin.game.media-mixes.titles.detach');
    Route::get('media-mixes/{id}/related-products', [AdminGameMediaMixController::class, 'relatedProductsIndex'])->name('api.v1.admin.game.media-mixes.related-products.index');
    Route::post('media-mixes/{id}/related-products', [AdminGameMediaMixController::class, 'relatedProductsAttach'])->name('api.v1.admin.game.media-mixes.related-products.attach');
    Route::put('media-mixes/{id}/related-products', [AdminGameMediaMixController::class, 'relatedProductsSync'])->name('api.v1.admin.game.media-mixes.related-products.sync');
    Route::delete('media-mixes/{id}/related-products/{relatedProductId}', [AdminGameMediaMixController::class, 'relatedProductsDetach'])->name('api.v1.admin.game.media-mixes.related-products.detach');
});

// 認証が必要なAPI
Route::middleware(['web', 'auth:web'])->group(function () {
    Route::post('user/favorite/toggle', [UserFavoriteController::class, 'toggle'])->name('api.user.favorite.toggle');
});


