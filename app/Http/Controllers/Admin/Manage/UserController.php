<?php

namespace App\Http\Controllers\Admin\Manage;

use App\Defines\AdminDefine;
use App\Http\Controllers\Admin\AbstractAdminController;
use App\Http\Requests\Admin\Manage\UserFearMeterRestrictionStoreRequest;
use App\Http\Requests\Admin\Manage\UserPasswordUpdateRequest;
use App\Models\User;
use App\Models\UserFearMeterRestriction;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class UserController extends AbstractAdminController
{
    /**
     * ユーザー一覧
     *
     * @param Request $request
     * @return Application|Factory|View
     */
    public function index(Request $request): Application|Factory|View
    {
        return view('admin.manage.user.index', $this->search($request));
    }

    /**
     * 検索処理
     *
     * @param Request $request
     * @return array
     */
    private function search(Request $request): array
    {
        $query = User::query()->orderByDesc('created_at');

        $searchStatus = $request->query('status', '');
        $searchKeyword = trim($request->query('keyword', ''));

        if ($searchStatus === 'active') {
            $query->whereNull('withdrawn_at');
        } elseif ($searchStatus === 'withdrawn') {
            $query->whereNotNull('withdrawn_at');
        }

        if ($searchKeyword !== '') {
            $query->where(function ($q) use ($searchKeyword) {
                $q->where('name', 'like', '%' . $searchKeyword . '%')
                    ->orWhere('email', 'like', '%' . $searchKeyword . '%')
                    ->orWhere('show_id', 'like', '%' . $searchKeyword . '%');
            });
        }

        $search = [
            'status' => $searchStatus,
            'keyword' => $searchKeyword,
        ];

        $this->saveSearchSession($search);

        return [
            'search' => $search,
            'users' => $query->paginate(AdminDefine::ITEMS_PER_PAGE),
            'statuses' => [
                '' => 'All',
                'active' => 'Active (未退会)',
                'withdrawn' => 'Withdrawn (退会済み)',
            ],
        ];
    }

    /**
     * ユーザー詳細
     *
     * @param User $user
     * @return Application|Factory|View
     */
    public function show(User $user): Application|Factory|View
    {
        $activeFearMeterRestriction = UserFearMeterRestriction::query()
            ->where('user_id', $user->id)
            ->active()
            ->first();

        return view('admin.manage.user.detail', [
            'model' => $user,
            'activeFearMeterRestriction' => $activeFearMeterRestriction,
        ]);
    }

    /**
     * パスワード変更画面
     *
     * @param User $user
     * @return Application|Factory|View
     */
    public function editPassword(User $user): Application|Factory|View
    {
        return view('admin.manage.user.password', [
            'model' => $user,
        ]);
    }

    /**
     * パスワード更新処理
     *
     * @param UserPasswordUpdateRequest $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(UserPasswordUpdateRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->password = $validated['password'];
        $user->save();

        return redirect()->route('Admin.Manage.User.Show', $user)
            ->with('success', 'パスワードを更新しました。');
    }

    /**
     * ユーザー削除
     *
     * @param User $user
     * @return RedirectResponse
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('Admin.Manage.User')
            ->with('success', 'ユーザーを削除しました。');
    }

    /**
     * ユーザーの怖さメーター入力制限
     *
     * @param UserFearMeterRestrictionStoreRequest $request
     * @param User $user
     * @return RedirectResponse
     */
    public function storeFearMeterRestriction(
        UserFearMeterRestrictionStoreRequest $request,
        User $user
    ): RedirectResponse {
        $activeExists = UserFearMeterRestriction::query()
            ->where('user_id', $user->id)
            ->active()
            ->exists();
        if ($activeExists) {
            return redirect()->back()->with('warning', '既に怖さメーター入力制限中です。');
        }

        UserFearMeterRestriction::create([
            'user_id' => $user->id,
            'reason' => $request->validated('reason'),
            'source' => $request->validated('source', 'manual'),
            'is_active' => true,
            'started_at' => now(),
            'created_by_admin_id' => auth()->guard('admin')->id(),
        ]);

        return redirect()->route('Admin.Manage.User.Show', $user)
            ->with('success', '怖さメーター入力制限を設定しました。');
    }

    /**
     * ユーザーの怖さメーター入力制限解除
     *
     * @param User $user
     * @return RedirectResponse
     */
    public function releaseFearMeterRestriction(User $user): RedirectResponse
    {
        $restriction = UserFearMeterRestriction::query()
            ->where('user_id', $user->id)
            ->active()
            ->latest('id')
            ->first();
        if ($restriction === null) {
            return redirect()->back()->with('warning', '有効な入力制限がありません。');
        }

        $restriction->is_active = false;
        $restriction->ended_at = now();
        $restriction->released_by_admin_id = auth()->guard('admin')->id();
        $restriction->save();

        return redirect()->route('Admin.Manage.User.Show', $user)
            ->with('success', '怖さメーター入力制限を解除しました。');
    }
}


