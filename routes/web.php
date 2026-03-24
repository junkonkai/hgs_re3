<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\GameFearMeterCommentController;
use App\Http\Controllers\HgnController;
use App\Http\Controllers\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [HgnController::class, 'root'])->name('Root');
Route::get('/logo', [HgnController::class, 'logo'])->name('Logo');

// アカウント（ログイン系）
Route::get('/login', [AccountController::class, 'login'])->name('Account.Login');
Route::post('/auth', [AccountController::class, 'auth'])->name('Account.Auth');
Route::get('/logout', [AccountController::class, 'logout'])->name('Account.Logout');
Route::get('/auth/github', [AccountController::class, 'redirectToGitHub'])->middleware('throttle:10,10')->name('Account.GitHub.Redirect');
Route::get('/auth/github/callback', [AccountController::class, 'handleGitHubCallback'])->middleware('throttle:10,10')->name('Account.GitHub.Callback');
Route::get('/register', [AccountController::class, 'register'])->name('Account.Register');
Route::post('/register', [AccountController::class, 'store'])->middleware('throttle:10,10')->name('Account.Register.Store');
Route::get('/register/complete/{token}', [AccountController::class, 'showCompleteRegistration'])->name('Account.Register.Complete');
Route::post('/register/complete/{token}', [AccountController::class, 'completeRegistration'])->name('Account.Register.Complete.Store');
Route::get('/password-reset', [AccountController::class, 'showPasswordReset'])->name('Account.PasswordReset');
Route::post('/password-reset', [AccountController::class, 'storePasswordReset'])->middleware('throttle:10,10')->name('Account.PasswordReset.Store');
Route::get('/password-reset/complete/{token}', [AccountController::class, 'showPasswordResetComplete'])->name('Account.PasswordReset.Complete');
Route::post('/password-reset/complete/{token}', [AccountController::class, 'completePasswordReset'])->name('Account.PasswordReset.Complete.Store');

Route::group(['prefix' => 'user'], function () {
    // マイページ（認証が必要）
    Route::middleware('auth')->group(function () {
        Route::get('my-node', [User\MyNodeController::class, 'top'])->name('User.MyNode.Top');
        Route::get('my-node/profile', [User\MyNodeController::class, 'profile'])->name('User.MyNode.Profile');
        Route::post('my-node/profile', [User\MyNodeController::class, 'profileUpdate'])->name('User.MyNode.Profile.Update');
        Route::get('my-node/email', [User\MyNodeController::class, 'email'])->name('User.MyNode.Email');
        Route::post('my-node/email', [User\MyNodeController::class, 'emailUpdate'])->name('User.MyNode.Email.Update');
        Route::get('my-node/password', [User\MyNodeController::class, 'password'])->name('User.MyNode.Password');
        Route::post('my-node/password', [User\MyNodeController::class, 'passwordUpdate'])->name('User.MyNode.Password.Update');
        Route::post('my-node/password-set', [User\MyNodeController::class, 'passwordSetUpdate'])->name('User.MyNode.PasswordSet.Update');
        Route::get('my-node/social-accounts', [User\MyNodeController::class, 'socialAccounts'])->name('User.MyNode.SocialAccounts');
        Route::get('my-node/social-accounts/link/{provider}', [User\MyNodeController::class, 'redirectToLinkProvider'])->name('User.MyNode.SocialAccounts.Link');
        Route::post('my-node/social-accounts/unlink', [User\MyNodeController::class, 'unlinkSocialAccount'])->name('User.MyNode.SocialAccounts.Unlink');
        Route::get('my-node/withdraw', [User\MyNodeController::class, 'withdraw'])->name('User.MyNode.Withdraw');
        Route::post('my-node/withdraw', [User\MyNodeController::class, 'withdrawStore'])->name('User.MyNode.Withdraw.Store');

        // フォロー/お気に入り
        Route::get('follow/favorite-titles', [User\FollowController::class, 'favoriteTitles'])->name('User.Follow.FavoriteTitles');
    });
    Route::get('my-node/email/verify/{token}', [User\MyNodeController::class, 'emailVerify'])->name('User.MyNode.Email.Verify');

    // 怖さメーター
    Route::get('fear-meter', [User\FearMeterController::class, 'index'])->name('User.FearMeter.Index');
    Route::get('fear-meter/{titleKey}/form', [User\FearMeterController::class, 'form'])->name('User.FearMeter.Form');
    Route::post('fear-meter', [User\FearMeterController::class, 'store'])->name('User.FearMeter.Form.Store');
    Route::delete('fear-meter', [User\FearMeterController::class, 'destroy'])->name('User.FearMeter.Form.Delete');
});

use App\Http\Controllers\Admin;
// 管理用
Route::group(['prefix' => 'admin'], function () {
    // ログイン系
    Route::get('login', [Admin\AdminController::class, 'login'])->name('Admin.Login');
    Route::post('auth', [Admin\AdminController::class, 'auth'])->name('Admin.Auth');
    Route::get('logout', [Admin\AdminController::class, 'logout'])->name('Admin.Logout');

    // ここからは認証が必要
    Route::middleware (['admin', 'auth:admin'])->group(function () {
        // 管理トップ
        Route::get('', [Admin\AdminController::class, 'top'])->name('Admin.Dashboard');

        // 運営
        Route::group(['prefix' => 'manage'], function () {
            // お知らせ
            Route::resource('information', Admin\Manage\InformationController::class)->names([
                'index'   => 'Admin.Manage.Information',
                'create'  => 'Admin.Manage.Information.Create',
                'store'   => 'Admin.Manage.Information.Store',
                'show'    => 'Admin.Manage.Information.Show',
                'edit'    => 'Admin.Manage.Information.Edit',
                'update'  => 'Admin.Manage.Information.Update',
                'destroy' => 'Admin.Manage.Information.Destroy',
            ]);

            // 問い合わせ
            Route::get('contact', [Admin\Manage\ContactController::class, 'index'])->name('Admin.Manage.Contact');
            Route::get('contact/{contact}', [Admin\Manage\ContactController::class, 'show'])->name('Admin.Manage.Contact.Show');
            Route::post('contact/{contact}/response', [Admin\Manage\ContactController::class, 'storeResponse'])->name('Admin.Manage.Contact.StoreResponse');
            Route::post('contact/{contact}/status', [Admin\Manage\ContactController::class, 'updateStatus'])->name('Admin.Manage.Contact.UpdateStatus');

            // ユーザー
            Route::get('user', [Admin\Manage\UserController::class, 'index'])->name('Admin.Manage.User');
            Route::get('user/{user}', [Admin\Manage\UserController::class, 'show'])->name('Admin.Manage.User.Show');
            Route::get('user/{user}/password', [Admin\Manage\UserController::class, 'editPassword'])->name('Admin.Manage.User.Password');
            Route::post('user/{user}/password', [Admin\Manage\UserController::class, 'updatePassword'])->name('Admin.Manage.User.Password.Update');
            Route::post('user/{user}/fear-meter-restrictions', [Admin\Manage\UserController::class, 'storeFearMeterRestriction'])->name('Admin.Manage.User.FearMeterRestriction.Store');
            Route::post('user/{user}/fear-meter-restrictions/release', [Admin\Manage\UserController::class, 'releaseFearMeterRestriction'])->name('Admin.Manage.User.FearMeterRestriction.Release');
            Route::delete('user/{user}', [Admin\Manage\UserController::class, 'destroy'])->name('Admin.Manage.User.Destroy');

            // 怖さメーター通報
            Route::get('fear-meter-report', [Admin\Manage\FearMeterReportController::class, 'index'])->name('Admin.Manage.FearMeterReport');
            Route::get('fear-meter-report/{report}', [Admin\Manage\FearMeterReportController::class, 'show'])->name('Admin.Manage.FearMeterReport.Show');
            Route::post('fear-meter-report/{report}/status', [Admin\Manage\FearMeterReportController::class, 'updateStatus'])->name('Admin.Manage.FearMeterReport.Status');
            Route::post('fear-meter-report/{report}/restrict-user', [Admin\Manage\FearMeterReportController::class, 'restrictUser'])->name('Admin.Manage.FearMeterReport.RestrictUser');
        });

        // マスター
        Route::group(['prefix' => 'master'], function () {
            // メーカー
            $prefix = 'maker';
            Route::group(['prefix' => 'maker'], function () use ($prefix) {
                $basename = 'Admin.Game.Maker';
                $class = Admin\Game\MakerController::class;
                Route::get('/', [$class, 'index'])->name($basename);
                Route::get('add', [$class, 'add'])->name("{$basename}.Add");
                Route::post('add', [$class, 'store'])->name("{$basename}.Store");
                Route::get('edit_multi', [$class, 'editMulti'])->name("{$basename}.EditMulti");
                Route::put('edit_multi', [$class, 'updateMulti'])->name("{$basename}.UpdateMulti");
                Route::get('{' . $prefix . '}/edit', [$class, 'edit'])->name("{$basename}.Edit");
                Route::put('{' . $prefix . '}/edit', [$class, 'update'])->name("{$basename}.Update");
                Route::get('{' . $prefix . '}/link_package', [$class, 'linkPackage'])->name("{$basename}.LinkPackage");
                Route::post('{' . $prefix . '}/link_package', [$class, 'syncPackage'])->name("{$basename}.SyncPackage");
                Route::get('{' . $prefix . '}', [$class, 'detail'])->name("{$basename}.Detail");
                Route::delete('{' . $prefix . '}', [$class, 'delete'])->name("{$basename}.Delete");
            });

            // プラットフォーム
            $prefix = 'platform';
            Route::group(['prefix' => 'platform'], function () use ($prefix) {
                $basename = 'Admin.Game.Platform';
                $class = Admin\Game\PlatformController::class;
                Route::get('/', [$class, 'index'])->name($basename);
                Route::get('add', [$class, 'add'])->name("{$basename}.Add");
                Route::post('add', [$class, 'store'])->name("{$basename}.Store");
                Route::get('edit_multi', [$class, 'editMulti'])->name("{$basename}.EditMulti");
                Route::put('edit_multi', [$class, 'updateMulti'])->name("{$basename}.UpdateMulti");
                Route::get('{' . $prefix . '}/edit', [$class, 'edit'])->name("{$basename}.Edit");
                Route::put('{' . $prefix . '}/edit', [$class, 'update'])->name("{$basename}.Update");
                Route::get('{' . $prefix . '}', [$class, 'detail'])->name("{$basename}.Detail");
                Route::delete('{' . $prefix . '}', [$class, 'delete'])->name("{$basename}.Delete");
                Route::get('{' . $prefix . '}/link_related_product', [$class, 'linkRelatedProduct'])->name("{$basename}.LinkRelatedProduct");
                Route::post('{' . $prefix . '}/link_related_product', [$class, 'syncRelatedProduct'])->name("{$basename}.SyncRelatedProduct");
            });

            // フランチャイズ
            $prefix = 'franchise';
            Route::group(['prefix' => 'franchise'], function () use ($prefix) {
                $basename = 'Admin.Game.Franchise';
                $class = Admin\Game\FranchiseController::class;
                Route::get('/', [$class, 'index'])->name($basename);
                Route::get('add', [$class, 'add'])->name("{$basename}.Add");
                Route::post('add', [$class, 'store'])->name("{$basename}.Store");
                Route::get('edit_multi', [$class, 'editMulti'])->name("{$basename}.EditMulti");
                Route::put('edit_multi', [$class, 'updateMulti'])->name("{$basename}.UpdateMulti");
                Route::get('{' . $prefix . '}/edit', [$class, 'edit'])->name("{$basename}.Edit");
                Route::put('{' . $prefix . '}/edit', [$class, 'update'])->name("{$basename}.Update");
                Route::get('{' . $prefix . '}/link_series', [$class, 'linkSeries'])->name("{$basename}.LinkSeries");
                Route::post('{' . $prefix . '}/link_series', [$class, 'syncSeries'])->name("{$basename}.SyncSeries");
                Route::get('{' . $prefix . '}/link_title', [$class, 'linkTitle'])->name("{$basename}.LinkTitle");
                Route::post('{' . $prefix . '}/link_title', [$class, 'syncTitle'])->name("{$basename}.SyncTitle");
                Route::get('{' . $prefix . '}/link_tree', [$class, 'linkTree'])->name("{$basename}.LinkTree");
                Route::get('{' . $prefix . '}', [$class, 'detail'])->name("{$basename}.Detail");
                Route::delete('{' . $prefix . '}', [$class, 'delete'])->name("{$basename}.Delete");
            });

            // シリーズ
            $prefix = 'series';
            Route::group(['prefix' => $prefix], function () use ($prefix) {
                $basename = 'Admin.Game.Series';
                $class = Admin\Game\SeriesController::class;
                Route::get('/', [$class, 'index'])->name($basename);
                Route::get('add', [$class, 'add'])->name("{$basename}.Add");
                Route::post('add', [$class, 'store'])->name("{$basename}.Store");
                Route::get('{' . $prefix . '}/edit', [$class, 'edit'])->name("{$basename}.Edit");
                Route::put('{' . $prefix . '}/edit', [$class, 'update'])->name("{$basename}.Update");
                Route::get('{' . $prefix . '}/link_title', [$class, 'linkTitle'])->name("{$basename}.LinkTitle");
                Route::post('{' . $prefix . '}/link_title', [$class, 'syncTitle'])->name("{$basename}.SyncTitle");
                Route::get('{' . $prefix . '}', [$class, 'detail'])->name("{$basename}.Detail");
                Route::delete('{' . $prefix . '}', [$class, 'delete'])->name("{$basename}.Delete");
            });

            // タイトル
            $prefix = 'title';
            Route::group(['prefix' => $prefix], function () use ($prefix) {
                $basename = 'Admin.Game.Title';
                $class = Admin\Game\TitleController::class;
                Route::get('/', [$class, 'index'])->name($basename);
                Route::get('add', [$class, 'add'])->name("{$basename}.Add");
                Route::post('add', [$class, 'store'])->name("{$basename}.Store");
                Route::get('edit_multi', [$class, 'editMulti'])->name("{$basename}.EditMulti");
                Route::put('edit_multi', [$class, 'updateMulti'])->name("{$basename}.UpdateMulti");
                Route::get('{' . $prefix . '}/edit', [$class, 'edit'])->name("{$basename}.Edit");
                Route::put('{' . $prefix . '}/edit', [$class, 'update'])->name("{$basename}.Update");
                Route::get('{' . $prefix . '}/link_franchise', [$class, 'linkFranchise'])->name("{$basename}.LinkFranchise");
                Route::post('{' . $prefix . '}/link_franchise', [$class, 'syncFranchise'])->name("{$basename}.SyncFranchise");
                Route::get('{' . $prefix . '}/link_series', [$class, 'linkSeries'])->name("{$basename}.LinkSeries");
                Route::post('{' . $prefix . '}/link_series', [$class, 'syncSeries'])->name("{$basename}.SyncSeries");
                Route::get('{' . $prefix . '}/link_package_group', [$class, 'linkPackageGroup'])->name("{$basename}.LinkPackageGroup");
                Route::post('{' . $prefix . '}/link_package_group', [$class, 'syncPackageGroup'])->name("{$basename}.SyncPackageGroup");
                Route::get('{' . $prefix . '}/edit_package_group_multi', [$class, 'editPackageGroupMulti'])->name("{$basename}.EditPackageGroupMulti");
                Route::put('{' . $prefix . '}/edit_package_group_multi', [$class, 'updatePackageGroupMulti'])->name("{$basename}.UpdatePackageGroupMulti");
                Route::get('{' . $prefix . '}/edit_package_multi', [$class, 'editPackageMulti'])->name("{$basename}.EditPackageMulti");
                Route::put('{' . $prefix . '}/edit_package_multi', [$class, 'updatePackageMulti'])->name("{$basename}.UpdatePackageMulti");
                Route::get('{' . $prefix . '}/link_related_product', [$class, 'linkRelatedProduct'])->name("{$basename}.LinkRelatedProduct");
                Route::post('{' . $prefix . '}/link_related_product', [$class, 'syncRelatedProduct'])->name("{$basename}.SyncRelatedProduct");
                Route::get('{' . $prefix . '}/link_media_mix', [$class, 'linkMediaMix'])->name("{$basename}.LinkMediaMix");
                Route::post('{' . $prefix . '}/link_media_mix', [$class, 'syncMediaMix'])->name("{$basename}.SyncMediaMix");
                Route::get('{' . $prefix . '}', [$class, 'detail'])->name("{$basename}.Detail");
                Route::delete('{' . $prefix . '}/fear-meter/{user}', [$class, 'deleteFearMeter'])->name("{$basename}.DeleteFearMeter");
                Route::delete('{' . $prefix . '}', [$class, 'delete'])->name("{$basename}.Delete");
            });

            // パッケージグループ
            $prefix = 'package_group';
            Route::group(['prefix' => $prefix], function () use ($prefix) {
                $basename = 'Admin.Game.PackageGroup';
                $class = Admin\Game\PackageGroupController::class;
                $prefix = 'packageGroup';
                Route::get('/', [$class, 'index'])->name($basename);
                Route::get('add', [$class, 'add'])->name("{$basename}.Add");
                Route::post('add', [$class, 'store'])->name("{$basename}.Store");
                Route::get('{' . $prefix . '}/edit', [$class, 'edit'])->name("{$basename}.Edit");
                Route::put('{' . $prefix . '}/edit', [$class, 'update'])->name("{$basename}.Update");
                Route::get('{' . $prefix . '}/link_package', [$class, 'linkPackage'])->name("{$basename}.LinkPackage");
                Route::post('{' . $prefix . '}/link_package', [$class, 'syncPackage'])->name("{$basename}.SyncPackage");
                Route::get('{' . $prefix . '}/link_title', [$class, 'linkTitle'])->name("{$basename}.LinkTitle");
                Route::post('{' . $prefix . '}/link_title', [$class, 'syncTitle'])->name("{$basename}.SyncTitle");
                Route::get('{' . $prefix . '}/edit_package_multi', [$class, 'editPackageMulti'])->name("{$basename}.EditPackageMulti");
                Route::put('{' . $prefix . '}/update_package_multi', [$class, 'updatePackageMulti'])->name("{$basename}.UpdatePackageMulti");
                Route::get('{' . $prefix . '}', [$class, 'detail'])->name("{$basename}.Detail");
                Route::delete('{' . $prefix . '}', [$class, 'delete'])->name("{$basename}.Delete");
            });

            // パッケージ
            $prefix = 'package';
            Route::group(['prefix' => $prefix], function () use ($prefix) {
                $basename = 'Admin.Game.Package';
                $class = Admin\Game\PackageController::class;
                Route::get('/', [$class, 'index'])->name($basename);
                Route::get('add', [$class, 'add'])->name("{$basename}.Add");
                Route::post('add', [$class, 'store'])->name("{$basename}.Store");
                Route::get('edit_multi', [$class, 'editMulti'])->name("{$basename}.EditMulti");
                Route::put('edit_multi', [$class, 'updateMulti'])->name("{$basename}.UpdateMulti");
                Route::get('edit_shop_multi', [$class, 'editShopMulti'])->name("{$basename}.EditShopMulti");
                Route::put('edit_shop_multi', [$class, 'updateShopMulti'])->name("{$basename}.UpdateShopMulti");

                Route::get('{' . $prefix . '}/edit', [$class, 'edit'])->name("{$basename}.Edit");
                Route::put('{' . $prefix . '}/edit', [$class, 'update'])->name("{$basename}.Update");
                Route::get('{' . $prefix . '}/copy', [$class, 'copy'])->name("{$basename}.Copy");
                Route::post('{' . $prefix . '}/copy', [$class, 'makeCopy'])->name("{$basename}.MakeCopy");
                Route::get('{' . $prefix . '}/link_maker', [$class, 'linkMaker'])->name("{$basename}.LinkMaker");
                Route::post('{' . $prefix . '}/link_maker', [$class, 'syncMaker'])->name("{$basename}.SyncMaker");
                Route::get('{' . $prefix . '}/link_title', [$class, 'linkTitle'])->name("{$basename}.LinkTitle");
                Route::post('{' . $prefix . '}/link_title', [$class, 'syncTitle'])->name("{$basename}.SyncTitle");
                Route::get('{' . $prefix . '}/link_package_group', [$class, 'linkPackageGroup'])->name("{$basename}.LinkPackageGroup");
                Route::post('{' . $prefix . '}/link_package_group', [$class, 'syncPackageGroup'])->name("{$basename}.SyncPackageGroup");

                Route::get('{' . $prefix . '}/shop/add', [$class, 'addShop'])->name("{$basename}.AddShop");
                Route::post('{' . $prefix . '}/shop/add', [$class, 'storeShop'])->name("{$basename}.StoreShop");
                Route::get('{' . $prefix . '}/shop/{pkgShop}/edit', [$class, 'editShop'])->name("{$basename}.EditShop");
                Route::put('{' . $prefix . '}/shop/{pkgShop}/edit', [$class, 'updateShop'])->name("{$basename}.UpdateShop");
                Route::delete('{' . $prefix . '}/shop/{pkgShop}', [$class, 'deleteShop'])->name("{$basename}.DeleteShop");

                Route::get('{' . $prefix . '}', [$class, 'detail'])->name("{$basename}.Detail");
                Route::delete('{' . $prefix . '}', [$class, 'delete'])->name("{$basename}.Delete");
            });

            // 関連商品
            $prefix = 'relatedProduct';
            Route::group(['prefix' => 'related_product'], function () use ($prefix) {
                $basename = 'Admin.Game.RelatedProduct';
                $class = Admin\Game\RelatedProductController::class;
                Route::get('/', [$class, 'index'])->name($basename);
                Route::get('add', [$class, 'add'])->name("{$basename}.Add");
                Route::post('add', [$class, 'store'])->name("{$basename}.Store");
                Route::get('edit_multi', [$class, 'editMulti'])->name("{$basename}.EditMulti");
                Route::put('edit_multi', [$class, 'updateMulti'])->name("{$basename}.UpdateMulti");
                Route::get('{' . $prefix . '}/edit', [$class, 'edit'])->name("{$basename}.Edit");
                Route::put('{' . $prefix . '}/edit', [$class, 'update'])->name("{$basename}.Update");
                Route::get('{' . $prefix . '}/copy', [$class, 'copy'])->name("{$basename}.Copy");
                Route::post('{' . $prefix . '}/copy', [$class, 'makeCopy'])->name("{$basename}.MakeCopy");
                Route::get('{' . $prefix . '}/link_platform', [$class, 'linkPlatform'])->name("{$basename}.LinkPlatform");
                Route::post('{' . $prefix . '}/link_platform', [$class, 'syncPlatform'])->name("{$basename}.SyncPlatform");
                Route::get('{' . $prefix . '}/link_title', [$class, 'linkTitle'])->name("{$basename}.LinkTitle");
                Route::post('{' . $prefix . '}/link_title', [$class, 'syncTitle'])->name("{$basename}.SyncTitle");
                Route::get('{' . $prefix . '}/link_media_mix', [$class, 'linkMediaMix'])->name("{$basename}.LinkMediaMix");
                Route::post('{' . $prefix . '}/link_media_mix', [$class, 'syncMediaMix'])->name("{$basename}.SyncMediaMix");
                Route::get('{' . $prefix . '}/shop/add', [$class, 'addShop'])->name("{$basename}.AddShop");
                Route::post('{' . $prefix . '}/shop/add', [$class, 'storeShop'])->name("{$basename}.StoreShop");
                Route::get('{' . $prefix . '}/shop/{shop}/edit', [$class, 'editShop'])->name("{$basename}.EditShop");
                Route::put('{' . $prefix . '}/shop/{shop}/edit', [$class, 'updateShop'])->name("{$basename}.UpdateShop");
                Route::delete('{' . $prefix . '}/shop/{shop}', [$class, 'deleteShop'])->name("{$basename}.DeleteShop");
                Route::get('{' . $prefix . '}', [$class, 'detail'])->name("{$basename}.Detail");
                Route::delete('{' . $prefix . '}', [$class, 'delete'])->name("{$basename}.Delete");
            });

            // メディアミックスグループ
            $prefix = 'media_mix_group';
            Route::group(['prefix' => $prefix], function () use ($prefix) {
                $basename = 'Admin.Game.MediaMixGroup';
                $class = Admin\Game\MediaMixGroupController::class;
                Route::get('/', [$class, 'index'])->name($basename);
                Route::get('add', [$class, 'add'])->name("{$basename}.Add");
                Route::post('add', [$class, 'store'])->name("{$basename}.Store");
                Route::get('{' . $prefix . '}/edit', [$class, 'edit'])->name("{$basename}.Edit");
                Route::put('{' . $prefix . '}/edit', [$class, 'update'])->name("{$basename}.Update");
                Route::get('{' . $prefix . '}/link_media_mix', [$class, 'linkMediaMix'])->name("{$basename}.LinkMediaMix");
                Route::post('{' . $prefix . '}/link_media_mix', [$class, 'syncMediaMix'])->name("{$basename}.SyncMediaMix");
                Route::get('{' . $prefix . '}', [$class, 'detail'])->name("{$basename}.Detail");
                Route::delete('{' . $prefix . '}', [$class, 'delete'])->name("{$basename}.Delete");
            });

            // メディアミックス
            $prefix = 'media_mix';
            Route::group(['prefix' => $prefix], function () use ($prefix) {
                $basename = 'Admin.Game.MediaMix';
                $class = Admin\Game\MediaMixController::class;
                Route::get('/', [$class, 'index'])->name($basename);
                Route::get('add', [$class, 'add'])->name("{$basename}.Add");
                Route::post('add', [$class, 'store'])->name("{$basename}.Store");
                Route::get('edit_multi', [$class, 'editMulti'])->name("{$basename}.EditMulti");
                Route::put('edit_multi', [$class, 'updateMulti'])->name("{$basename}.UpdateMulti");
                Route::get('{' . $prefix . '}/edit', [$class, 'edit'])->name("{$basename}.Edit");
                Route::put('{' . $prefix . '}/edit', [$class, 'update'])->name("{$basename}.Update");
                Route::get('{' . $prefix . '}/copy', [$class, 'copy'])->name("{$basename}.Copy");
                Route::get('{' . $prefix . '}/link_media_mix_group', [$class, 'linkMediaMixGroup'])->name("{$basename}.LinkMediaMixGroup");
                Route::post('{' . $prefix . '}/link_media_mix_group', [$class, 'syncMediaMixGroup'])->name("{$basename}.SyncMediaMixGroup");
                Route::get('{' . $prefix . '}/link_related_product', [$class, 'linkRelatedProduct'])->name("{$basename}.LinkRelatedProduct");
                Route::post('{' . $prefix . '}/link_related_product', [$class, 'syncRelatedProduct'])->name("{$basename}.SyncRelatedProduct");
                Route::get('{' . $prefix . '}/link_title', [$class, 'linkTitle'])->name("{$basename}.LinkTitle");
                Route::post('{' . $prefix . '}/link_title', [$class, 'syncTitle'])->name("{$basename}.SyncTitle");
                Route::get('{' . $prefix . '}', [$class, 'detail'])->name("{$basename}.Detail");
                Route::delete('{' . $prefix . '}', [$class, 'delete'])->name("{$basename}.Delete");
            });
        });
    });
});


$class = HgnController::class;
Route::get('privacy', [$class, 'privacyPolicy'])->name('PrivacyPolicy');
Route::post('privacy/accept', [$class, 'acceptPrivacyPolicy'])->name('PrivacyPolicy.Accept');
Route::get('about', [$class, 'about'])->name('About');
Route::get('/info', [HgnController::class, 'infomations'])->name('Informations');
Route::get('/info/{info}', [HgnController::class, 'infomationDetail'])->name('InformationDetail');
Route::get('/contact', [ContactController::class, 'form'])->name('Contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('SendContact');
Route::get('/contact/{token}', [ContactController::class, 'show'])->name('Contact.Show');
Route::post('/contact/{token}/response', [ContactController::class, 'storeResponse'])->name('Contact.StoreResponse');
Route::post('/contact/{token}/cancel', [ContactController::class, 'cancel'])->name('Contact.Cancel');

// ゲーム
Route::group(['prefix' => 'game'], function () {
    $class = \App\Http\Controllers\GameController::class;
    // ホラーゲーム検索
    Route::get('/search', [$class, 'search'])->name('Game.Search');
    // フランチャイズ詳細
    Route::get('/franchise/{franchiseKey}', [$class, 'franchiseDetail'])->name('Game.FranchiseDetail');
    // フランチャイズ
    Route::get('/franchises/{prefix?}', [$class, 'franchises'])->name('Game.Franchises');
    // ホラーゲームラインナップ
    Route::get('/lineup', [$class, 'lineup'])->name('Game.Lineup');
    // タイトル詳細
    Route::get('/title/{titleKey}', [$class, 'titleDetail'])->name('Game.TitleDetail');
    // タイトル詳細（怖さメーターコメントログ）
    Route::get('/title/{titleKey}/fear-meter-comments', [$class, 'titleFearMeterComments'])->name('Game.TitleFearMeterComments');
    Route::middleware('auth')->group(function () {
        Route::post('/title/{titleKey}/fear-meter-comments/{logId}/like', [GameFearMeterCommentController::class, 'like'])->name('Game.TitleFearMeterComments.Like');
        Route::delete('/title/{titleKey}/fear-meter-comments/{logId}/like', [GameFearMeterCommentController::class, 'unlike'])->name('Game.TitleFearMeterComments.Unlike');
        Route::post('/title/{titleKey}/fear-meter-comments/{logId}/unlike', [GameFearMeterCommentController::class, 'unlike'])->name('Game.TitleFearMeterComments.UnlikePost');
        Route::post('/title/{titleKey}/fear-meter-comments/{logId}/report', [GameFearMeterCommentController::class, 'report'])->name('Game.TitleFearMeterComments.Report');
    });

    // メーカー詳細
    Route::get('/maker/{makerKey}', [$class, 'makerDetail'])->name('Game.MakerDetail');
    // メーカー
    Route::get('/maker', [$class, 'maker'])->name('Game.Maker');
    // プラットフォーム詳細
    Route::get('/platform/{platformKey}', [$class, 'platformDetail'])->name('Game.PlatformDetail');
    // プラットフォーム
    Route::get('/platform', [$class, 'platform'])->name('Game.Platform');
    // メディアミックス詳細
    Route::get('/media-mix/{mediaMixKey}', [$class, 'mediaMixDetail'])->name('Game.MediaMixDetail');
});
