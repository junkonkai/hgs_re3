# レビュー機能 実装計画（フェーズ別）

> 設計仕様: `docs/plan/review-feature.md` を参照

---

## 全体方針

- FearMeterの実装パターンを踏襲する（Dirty Flag, バッチ統計, ソフトデリート等）
- 各フェーズ完了後に単体動作確認してから次フェーズへ進む
- OGP画像生成（Rustマイクロサービス）はスコープ外（別プロジェクト）

---

## フェーズ一覧

| フェーズ | 内容 | 依存 | 状態 |
|---------|------|------|------|
| Phase 1 | 基盤：Enum・Migration・Model | なし | ✅ 完了 (2026-04-10) |
| Phase 2 | 怖さメーター直接編集対応（既存機能変更） | Phase 1 | ✅ 完了 (2026-04-10) |
| Phase 3 | レビュー投稿・下書き（ユーザー側） | Phase 1, 2 | ✅ 完了 (2026-04-10) |
| Phase 4 | レビュー表示（タイトル詳細・全件・個別） | Phase 3 | ✅ 完了 (2026-04-10) |
| Phase 5 | いいね・通報 | Phase 4 | ✅ 完了 (2026-04-10) |
| Phase 6 | 統計バッチ処理 | Phase 1 | ✅ 完了 (2026-04-10) |
| Phase 7 | 管理画面（通報対応・非表示・削除） | Phase 5 | ✅ 完了 (2026-04-10) |
| Phase 8 | マイページ（いいね一覧） | Phase 5 | ✅ 完了 (2026-04-11) |
| Phase 9 | SNS共有・OGPメタタグ | Phase 4 | 未着手 |

---

## Phase 1: 基盤（Enum・Migration・Model）✅ 完了

> 完了日: 2026-04-10  
> Migration は `php artisan migrate` で正常適用済み（13ファイル）

### 1-1. Enum 作成

**`app/Enums/PlayTime.php`**
```php
enum PlayTime: string {
    case Under1Hour  = 'under_1h';
    case Hour1To3    = '1h_to_3h';
    case Hour3To5    = '3h_to_5h';
    case Hour5To10   = '5h_to_10h';
    case Hour10To20  = '10h_to_20h';
    case Over20Hours = 'over_20h';
    // label(): string — 表示名を返す
    // selectList(): array — フォーム用リスト
}
```

**`app/Enums/PlayStatus.php`**
```php
enum PlayStatus: string {
    case Cleared = 'cleared';
    case Playing = 'playing';
    case Watched = 'watched';
    // label(): string
    // selectList(): array
}
```

**`app/Enums/HorrorTypeTag.php`**
```php
enum HorrorTypeTag: string {
    case JumpScare    = 'jump_scare';
    case Psychological = 'psychological';
    case Gore         = 'gore';
    case Atmosphere   = 'atmosphere';
    case Supernatural = 'supernatural';
    case Enclosed     = 'enclosed';
    case Chased       = 'chased';
    // label(): string
    // selectList(): array
}
```

### 1-2. Migration 作成

日付プレフィックスは `2026_04_10` を使用。

| ファイル名 | テーブル | 備考 |
|-----------|---------|------|
| `..._100000_create_user_game_title_reviews_table.php` | `user_game_title_reviews` | レビュー本体 |
| `..._100001_create_user_game_title_review_packages_table.php` | `user_game_title_review_packages` | プレイ環境（中間テーブル） |
| `..._100002_create_user_game_title_review_horror_type_tags_table.php` | `user_game_title_review_horror_type_tags` | ホラー種別タグ |
| `..._100003_create_user_game_title_review_logs_table.php` | `user_game_title_review_logs` | スナップショット |
| `..._100004_create_user_game_title_review_drafts_table.php` | `user_game_title_review_drafts` | 下書き |
| `..._100005_create_user_game_title_review_draft_packages_table.php` | `user_game_title_review_draft_packages` | 下書き用プレイ環境 |
| `..._100006_create_user_game_title_review_draft_horror_type_tags_table.php` | `user_game_title_review_draft_horror_type_tags` | 下書き用ホラー種別タグ |
| `..._100007_create_user_game_title_review_likes_table.php` | `user_game_title_review_likes` | いいね |
| `..._100008_create_user_game_title_review_reports_table.php` | `user_game_title_review_reports` | 通報 |
| `..._100009_create_game_title_review_statistics_table.php` | `game_title_review_statistics` | 統計キャッシュ |
| `..._100010_create_review_statistics_dirty_titles_table.php` | `review_statistics_dirty_titles` | ダーティフラグ |
| `..._100011_create_review_statistics_run_log_table.php` | `review_statistics_run_log` | バッチ実行ログ |

#### `user_game_title_reviews` の主要カラム

```
id, user_id, game_title_id
is_hidden (bool, default false), hidden_by_admin_id (nullable), hidden_at (nullable)
play_status (string), play_time (string, nullable)
body (text), has_spoiler (bool)
score_story (tinyint, nullable 0-4), score_atmosphere (tinyint, nullable 0-4), score_gameplay (tinyint, nullable 0-4)
user_score_adjustment (smallint, nullable, -20~+20)
base_score (tinyint, nullable 0-100), total_score (tinyint, nullable 0-100)
current_log_id (bigint, nullable)
ogp_image_path (string, nullable)
is_deleted (bool, default false)
unique: (user_id, game_title_id)
timestamps
```

#### `user_game_title_review_logs` の主要カラム

```
id, review_id, user_id
version (int)
play_status, play_time (nullable)
game_package_ids (json, nullable), horror_type_tags (json, nullable)
body, has_spoiler
score_story (nullable), score_atmosphere (nullable), score_gameplay (nullable)
user_score_adjustment (nullable)
base_score (nullable), total_score (nullable)
unique: (review_id, version)
created_at のみ（updated_at なし）
```

### 1-3. Model 作成

| モデル | テーブル | 備考 |
|-------|---------|------|
| `UserGameTitleReview` | `user_game_title_reviews` | リレーション: user, gameTitle, packages, horrorTypeTags, logs, currentLog, likes, reports |
| `UserGameTitleReviewPackage` | `user_game_title_review_packages` | |
| `UserGameTitleReviewHorrorTypeTag` | `user_game_title_review_horror_type_tags` | |
| `UserGameTitleReviewLog` | `user_game_title_review_logs` | `updated_at = null` |
| `UserGameTitleReviewDraft` | `user_game_title_review_drafts` | unique: (user_id, game_title_id) |
| `UserGameTitleReviewDraftPackage` | `user_game_title_review_draft_packages` | |
| `UserGameTitleReviewDraftHorrorTypeTag` | `user_game_title_review_draft_horror_type_tags` | |
| `UserGameTitleReviewLike` | `user_game_title_review_likes` | |
| `UserGameTitleReviewReport` | `user_game_title_review_reports` | |
| `GameTitleReviewStatistic` | `game_title_review_statistics` | `recalculate()` メソッド実装 |
| `ReviewStatisticsDirtyTitle` | `review_statistics_dirty_titles` | FearMeterStatisticsDirtyTitle と同パターン |
| `ReviewStatisticsRunLog` | `review_statistics_run_log` | |

#### `GameTitleReviewStatistic::recalculate()` の計算ロジック

```
review_count    ← is_deleted=false かつ is_hidden=false のレビュー件数
avg_total_score ← total_score の平均（NULL除外）
avg_story       ← score_story の平均（NULL除外）
avg_atmosphere  ← score_atmosphere の平均（NULL除外）
avg_gameplay    ← score_gameplay の平均（NULL除外）
件数が0になったら統計レコードを削除
```

#### スコア計算ヘルパー（`UserGameTitleReview` に静的メソッドとして実装）

```php
// ベーススコア = 怖さ×10 + ストーリー×5 + 雰囲気×5 + ゲーム性×5
// 総合スコア = clamp(base + adjustment, 0, 100)
// fear_meter は UserGameTitleFearMeter から取得（レビュー本体には持たない）
public static function calcBaseScore(?int $fearMeter, ?int $story, ?int $atmosphere, ?int $gameplay): ?int
public static function calcTotalScore(?int $base, ?int $adjustment): ?int
```

---

## Phase 2: 怖さメーター直接編集対応（既存機能変更）✅ 完了

> 完了日: 2026-04-10  
> バグ修正 (2026-04-10): `UserGameTitleFearMeter` は複合主キーのため `$model->save()` が使えない。`update()` クエリに変更済み。

レビュー機能と統合するにあたり、怖さメーターの「削除→再入力」方式を廃止し、直接編集可能にする。

### 2-1. Migration

**`app/Http/Migrations/..._add_action_to_user_game_title_fear_meter_logs_table.php`**

`user_game_title_fear_meter_logs` に `action` カラムを追加する。

| 値 | 意味 |
|----|------|
| 1 | 新規登録 |
| 2 | 編集 |
| 3 | 削除 |

`is_deleted` カラムは削除操作時（action=3）のみ `true` にする。既存の意味を維持する。

既存レコードへのデフォルト値は `1`（新規登録）を設定する。

### 2-2. Controller 変更（`FearMeterController`）

#### `store()` → upsert に変更

```php
// 変更前：alreadyExists のとき warning を返して終了
// 変更後：alreadyExists のとき既存レコードを UPDATE し、action=2 のログを記録

$existed = UserGameTitleFearMeter::where('user_id', $user->id)
    ->where('game_title_id', $title->id)
    ->first();

if ($existed) {
    $oldValue = $existed->fear_meter->value;
    $existed->fear_meter = $newFearMeter;
    $existed->save();

    UserGameTitleFearMeterLog::create([
        'user_id'        => $user->id,
        'game_title_id'  => $title->id,
        'old_fear_meter' => $oldValue,
        'new_fear_meter' => $newFearMeter,
        'comment'        => $comment,
        'action'         => 2, // 編集
    ]);

    // 値が変わった場合のみ dirty フラグを立てる
    if ($oldValue !== $newFearMeter) {
        FearMeterStatisticsDirtyTitle::updateOrCreate(['game_title_id' => $title->id]);
    }
} else {
    UserGameTitleFearMeter::create([...]);
    UserGameTitleFearMeterLog::create([..., 'action' => 1]); // 新規登録
    FearMeterStatisticsDirtyTitle::updateOrCreate(['game_title_id' => $title->id]);
}
```

#### `destroy()` の変更

レビューが存在する場合に、怖さメーター削除と同時にレビューもソフトデリートする（ユーザーへの確認は UI 側で実施）。

```php
// リクエストパラメータ: also_delete_review (bool)
// レビューが存在し also_delete_review=true のとき:
$review = UserGameTitleReview::where('user_id', $user->id)
    ->where('game_title_id', $title->id)
    ->where('is_deleted', false)
    ->first();
if ($review) {
    $review->is_deleted = true;
    $review->save();
    // 下書きも削除
    // ReviewStatisticsDirtyTitle::updateOrCreate
}
```

### 2-3. フォーム UI 変更（`user/fear_meter/form.blade.php`）

- 登録済みの場合も入力フォームを表示し、現在値を初期値として表示する
- 削除ボタンの確認メッセージを条件分岐：
  - レビューが存在しない → 「削除します。よろしいですか？」
  - レビューが存在する → 「怖さメーターを削除すると、レビューも一緒に削除されます。よろしいですか？」

コントローラ側でレビュー存在フラグ（`$hasReview`）をビューに渡す。

### 2-4. `FearMeterStatisticsDirtyTitle` 登録タイミングの変更

| 操作 | 変更前 | 変更後 |
|------|--------|--------|
| 新規登録 | 登録しない | 登録する |
| 編集 | — | **値が変わった場合のみ**登録する |
| 削除 | 登録する | 登録する（変更なし） |

---

## Phase 3: レビュー投稿・下書き（ユーザー側）✅ 完了

> 完了日: 2026-04-10  
> 実装メモ:
> - スコア入力は select ドロップダウン（0〜4 + 評価なし）で実装
> - 怖さメーター変更時のスコア再計算は `FearMeterFormInput.render()` で `input` イベントを dispatch し `ReviewFormInput` がリッスン
> - 下書き保存ボタンは `type="button"` で JS が form.action を切り替えてサブミット（ブラウザバリデーション回避）
> - レビュー削除時の怖さメーター同時削除は2段階confirm で制御

### 2-1. Form Requests

| ファイル | バリデーション対象 |
|---------|--------------|
| `ReviewDraftSaveRequest` | 下書き保存（body以外nullable） |
| `ReviewPublishRequest` | 公開（body必須、max:2000、play_status必須） |
| `ReviewDestroyRequest` | 削除（title_key, also_delete_fear_meter） |

### 2-2. Controller

**`app/Http/Controllers/User/ReviewController.php`**

| メソッド | ルート | 説明 |
|---------|-------|------|
| `index()` | GET `/user/review` | マイレビュー一覧（Pager, 10件/ページ） |
| `form(titleKey)` | GET `/user/review/{titleKey}/form` | 投稿・編集フォーム |
| `saveDraft()` | POST `/user/review/draft` | 下書き保存（upsert） |
| `publish()` | POST `/user/review` | 公開（新規 or 編集） |
| `discardDraft()` | DELETE `/user/review/draft` | 下書き破棄 |
| `destroy()` | DELETE `/user/review` | レビュー削除（ソフトデリート） |

#### `form()` の主要ロジック

1. GameTitle を titleKey で取得（404 guard）
2. ログインユーザーの既存レビューを取得（`is_deleted=false`）
3. 下書きを取得（`user_id + game_title_id`）
4. FearMeter を取得（フォームの初期値として表示）
5. タイトルに紐づく GamePackage 一覧を取得（プレイ環境選択用）
6. フォームに渡すデータ: `title, review, draft, fearMeter, packages`

#### `publish()` の主要ロジック

```
DB::transaction:
  1. FearMeter の作成 or 更新（怖さが入力されていれば）
  2. base_score / total_score を計算
  3. レビューが存在しない（新規 or is_deleted=true）→ INSERT / UPDATE
  4. レビューが存在する（編集）→ UPDATE
  5. ログを INSERT（version = 現在の最大+1）
  6. review.current_log_id を更新
  7. パッケージ・ホラータグを sync（delete → insert）
  8. 下書きを削除
  9. ReviewStatisticsDirtyTitle::updateOrCreate
```

#### `destroy()` の主要ロジック

```
DB::transaction:
  1. review.is_deleted = true
  2. also_delete_fear_meter が true なら FearMeter も削除
     + FearMeterStatisticsDirtyTitle::updateOrCreate
  3. 下書きが存在すれば削除
  4. ReviewStatisticsDirtyTitle::updateOrCreate
```

### 2-3. Routes 追記

```php
// routes/web.php — Route::group(['prefix' => 'user']) 内
Route::get('review', [User\ReviewController::class, 'index'])->name('User.Review.Index');
Route::get('review/{titleKey}/form', [User\ReviewController::class, 'form'])->name('User.Review.Form');
Route::post('review/draft', [User\ReviewController::class, 'saveDraft'])->name('User.Review.Draft.Save');
Route::delete('review/draft', [User\ReviewController::class, 'discardDraft'])->name('User.Review.Draft.Discard');
Route::post('review', [User\ReviewController::class, 'publish'])->name('User.Review.Publish');
Route::delete('review', [User\ReviewController::class, 'destroy'])->name('User.Review.Destroy');
```

### 2-4. Views

```
resources/views/user/review/
  ├── index.blade.php       # マイレビュー一覧
  └── form.blade.php        # 投稿・編集フォーム
```

#### フォームの構成要素

- プレイ状況（必須、PlayStatus ラジオ or セレクト）
- プレイ時間（任意、PlayTime セレクト）
- プレイ環境（任意、GamePackage チェックボックス）
- 怖さ（任意、FearMeter enum 0〜4 スライダー or ラジオ）
- ストーリー・雰囲気・ゲーム性（各0〜4）
- ユーザー調整（−20〜+20 スライダー）
- ホラー種別タグ（任意、複数選択チェックボックス）
- 本文（必須、max:2000）
- ネタバレフラグ（チェックボックス）
- ボタン: 「下書き保存」「公開する」

---

## Phase 4: レビュー表示

### 3-1. タイトル詳細ページへの統合

`GameController::titleDetail()` に以下を追加：

```php
// 統計
$reviewStatistic = GameTitleReviewStatistic::find($title->id);

// 新着レビュー（ネタバレなし優先、最大3件）
$recentReviews = UserGameTitleReview::where('game_title_id', $title->id)
    ->where('is_deleted', false)
    ->where('is_hidden', false)
    ->orderByRaw('has_spoiler ASC') // ネタバレなし優先
    ->orderByDesc('updated_at')
    ->limit(3)
    ->with(['user', 'horrorTypeTags'])
    ->get();
```

Blade に `[レビュー]` セクションを追加（怖さメーターの下）。

### 3-2. レビュー全件表示ページ

**Route:** `GET /game/title/{titleKey}/reviews` → `Game.TitleReviews`

**Controller:** `GameController::titleReviews()` or 新規 `GameReviewController`

- ソート: 新しい順（固定）
- ページネーション: 10件/ページ（`App\Support\Pager` 使用）
- ネタバレレビューは本文折りたたみ表示

**View:** `resources/views/game/title/reviews.blade.php`

### 3-3. レビュー個別ページ

**Route:** `GET /game/title/{titleKey}/review/{showId}` → `Game.TitleReview`

- `showId` = `users.show_id`
- 404 guard: タイトルキーとレビューのゲームタイトルが一致しているか確認
- `is_deleted=true` or `is_hidden=true` のときは適切なメッセージ表示
- SNS共有ボタン（X, LINE）

**View:** `resources/views/game/title/review.blade.php`

---

## Phase 5: いいね・通報

### 4-1. Controller

**`app/Http/Controllers/GameReviewController.php`**

| メソッド | ルート | 説明 |
|---------|-------|------|
| `like(titleKey, reviewId)` | POST `/game/title/{titleKey}/reviews/{reviewId}/like` | いいね（ログイン必須） |
| `unlike(titleKey, reviewId)` | DELETE `/game/title/{titleKey}/reviews/{reviewId}/like` | いいね解除 |
| `report(titleKey, reviewId)` | POST `/game/title/{titleKey}/reviews/{reviewId}/report` | 通報（ログイン必須） |

FearMeterCommentController と同パターン。

#### `like()` のロジック

```php
UserGameTitleReviewLike::firstOrCreate([
    'user_id'       => $user->id,
    'review_id'     => $review->id,
], [
    'review_log_id' => $review->current_log_id,
    'created_at'    => now(),
]);
```

#### `report()` のロジック

```php
UserGameTitleReviewReport::firstOrCreate(
    ['user_id' => $user->id, 'review_id' => $review->id],
    [
        'review_log_id' => $review->current_log_id,
        'reason'        => $request->input('reason'),
        'is_resolved'   => false,
    ]
);
// Discord 通知
app(DiscordWebhookService::class)->to(DiscordChannel::Contact)->username('HGN 通報Bot')
    ->send("レビューへの通報がありました。...");
```

### 4-2. Routes 追記

```php
// routes/web.php — ゲームタイトル関連
Route::post('/game/title/{titleKey}/reviews/{review}/like', [GameReviewController::class, 'like'])->name('Game.TitleReview.Like');
Route::delete('/game/title/{titleKey}/reviews/{review}/like', [GameReviewController::class, 'unlike'])->name('Game.TitleReview.Unlike');
Route::post('/game/title/{titleKey}/reviews/{review}/report', [GameReviewController::class, 'report'])->name('Game.TitleReview.Report');
```

---

## Phase 6: 統計バッチ処理

### 5-1. Artisan コマンド

**`app/Console/Commands/RecalculateReviewStatisticsCommand.php`**

```
php artisan review:recalculate-statistics [--force-full] [--no-progress]
```

`RecalculateFearMeterStatisticsCommand` と完全に同じパターン：
1. `ReviewStatisticsRunLog` から `last_completed_at` 取得
2. `updated_at > last_completed_at` な `user_game_title_reviews` の `game_title_id` を取得
3. `review_statistics_dirty_titles` の `game_title_id` とマージ
4. 各タイトルに対して `GameTitleReviewStatistic::recalculate()` を実行
5. `dirty_titles` を削除
6. `run_log` を更新

### 5-2. スケジュール登録

`routes/console.php` or `app/Console/Kernel.php` に追記：

```php
Schedule::command('review:recalculate-statistics --no-progress')
    ->everyFewHours(); // 怖さメーターと同じ頻度に合わせる
```

---

## Phase 7: 管理画面

### 6-1. レビュー通報管理

**`app/Http/Controllers/Admin/Manage/ReviewReportController.php`**

| メソッド | ルート | 説明 |
|---------|-------|------|
| `index()` | GET `/admin/manage/review-report` | 通報一覧 |
| `show($report)` | GET `/admin/manage/review-report/{report}` | 通報詳細（通報時バージョンの本文を表示） |
| `updateStatus($report)` | POST `/admin/manage/review-report/{report}/status` | 対応ステータス更新 |
| `hideReview($report)` | POST `/admin/manage/review-report/{report}/hide-review` | レビューを非表示 |
| `deleteReview($report)` | DELETE `/admin/manage/review-report/{report}/review` | レビューを物理削除 |

FearMeterReportController と同パターン。

#### 非表示（`is_hidden = true`）のロジック

```php
$review->is_hidden = true;
$review->hidden_by_admin_id = auth('admin')->id();
$review->hidden_at = now();
$review->save();
ReviewStatisticsDirtyTitle::updateOrCreate(['game_title_id' => $review->game_title_id]);
```

#### 物理削除のロジック

```php
DB::transaction(function () use ($review) {
    $review->likes()->delete();
    $review->reports()->delete();
    $review->logs()->delete();
    $review->packages()->delete();
    $review->horrorTypeTags()->delete();
    $review->delete(); // 物理削除
});
ReviewStatisticsDirtyTitle::updateOrCreate(['game_title_id' => $gameTitleId]);
// FearMeterは削除しない
```

### 6-2. Routes 追記

```php
// routes/web.php — admin/manage グループ内
Route::get('review-report', [Admin\Manage\ReviewReportController::class, 'index'])->name('Admin.Manage.ReviewReport');
Route::get('review-report/{report}', [Admin\Manage\ReviewReportController::class, 'show'])->name('Admin.Manage.ReviewReport.Show');
Route::post('review-report/{report}/status', [Admin\Manage\ReviewReportController::class, 'updateStatus'])->name('Admin.Manage.ReviewReport.Status');
Route::post('review-report/{report}/hide-review', [Admin\Manage\ReviewReportController::class, 'hideReview'])->name('Admin.Manage.ReviewReport.HideReview');
Route::delete('review-report/{report}/review', [Admin\Manage\ReviewReportController::class, 'deleteReview'])->name('Admin.Manage.ReviewReport.DeleteReview');
```

### 6-3. Views

```
resources/views/admin/manage/review_report/
  ├── index.blade.php
  └── show.blade.php
```

---

## Phase 8: マイページ（いいね一覧）

### 7-1. Controller（既存 MyNodeController or 新規）

**Route:** `GET /user/my-node/review-likes` → `User.MyNode.ReviewLikes`

- ソート: いいねした日時の新しい順
- ページネーション: 10件/ページ
- 変更検知: `like.review_log_id != review.current_log_id` → 「いいねした後に内容が更新されています」表示
- `is_deleted=true` → 「このレビューは削除されました」
- `is_hidden=true` → 「このレビューは非表示です」

### 7-2. View

`resources/views/user/my_node/review_likes.blade.php`

---

## Phase 9: SNS共有・OGPメタタグ

### 9-1. レビュー個別URL

すでに Phase 4 で実装（`/game/title/{titleKey}/review/{showId}`）。

### 8-2. OGPメタタグ（仮画像版）

個別レビューページの `<head>` に以下を出力：

```blade
<meta property="og:title" content="{{ $title->name }} のレビュー — {{ $review->user->show_id }}">
<meta property="og:description" content="...スコア情報...">
<meta property="og:image" content="{{ $review->ogp_image_path ? asset($review->ogp_image_path) : asset('img/ogp_default.png') }}">
```

OGP画像生成（Rustマイクロサービス）は別途実装。  
`ogp_image_path` カラムは Phase 1 のマイグレーションで追加済みのため、将来の統合が容易。

### 8-3. show_id変更時の注意書き

- ユーザー登録画面: Phase 2 完了後に追記
- show_id変更画面: Phase 2 完了後に追記
- レビューフォーム: Phase 2 のフォームに表示

---

## 退会処理への対応（Phase 1 完了後）

既存の退会バッチ（`user:purge-withdrawn`）による物理削除時に、レビュー関連テーブルも連動削除されるよう対応する。

### 対応方法

**方法A: 外部キー制約の CASCADE ON DELETE**
- `user_game_title_reviews.user_id` → CASCADE
- `user_game_title_review_drafts.user_id` → CASCADE
- `user_game_title_review_likes.user_id` → CASCADE（自分のいいね）
- `user_game_title_review_reports.user_id` → CASCADE（自分の通報）
- `user_game_title_review_logs.review_id` → CASCADE（review削除に連動）

**方法B: `User` モデルの `deleting` イベント**

どちらか既存パターンに合わせて採用。

---

## 未定事項・今後の検討

| 項目 | 内容 |
|-----|------|
| OGP画像自動生成 | Rustマイクロサービスは別プロジェクトとして別途実装 |
| 管理画面のレビュー一覧 | 直接アクセスする一覧（通報経由ではなく全レビュー検索）は Phase 7 以降で判断 |
| 評価軸スコアの表示方法 | フロントエンドのスライダーUI詳細は実装時に決定 |
