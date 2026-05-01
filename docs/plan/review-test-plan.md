# レビュー機能 テスト計画

> 実装計画: `docs/plan/review-implementation.md` を参照  
> 設計仕様: `docs/plan/review-feature.md` を参照

---

## 方針

| 種別 | フレームワーク | 配置場所 | 用途 |
|------|-------------|---------|------|
| Feature テスト（PHP） | PHPUnit + Laravel | `tests/Feature/` | ビジネスロジック・コマンド・スコア計算のユニット的検証 |
| E2E テスト | Playwright | `tests/e2e/` | ユーザー操作フロー全体の結合確認 |

### E2E テストの特殊な作り（既存テストからの規則）

- **ページ遷移後は必ず `waitForTreeAppeared(page)` を呼ぶ**  
  （`hgn.currentNode.nodeContentTree.appearStatus === 2` になるまで待機する独自関数）
- **SPAナビゲーション**なので `page.waitForLoadState('networkidle')` だけでは不十分  
- **ログインが必要なテスト**は `api/test/create-test-account` を叩いてアカウントを作成する
- **統計の検証**は `api/test/review/recalculate` を叩いてからバッチ結果を確認する  
  （怖さメーターと同様に `api/test/review/statistics` を用意する）
- **URLは相対パス**で指定（`page.goto('')` / `page.goto('user/review/...')` など。`'/'` は使わない）
- **テストのタイムアウト**は長めのフローで `test.setTimeout(90000)` または `120000` を設定する
- **JSエラー収集**を長いフローのテストには追加する（`page.on('pageerror', ...)` パターン）

---

## Feature テスト（PHP）

### 1. スコア計算ロジック

**`tests/Feature/Review/ReviewScoreCalculationTest.php`**

`UserGameTitleReview::calcBaseScore()` と `calcTotalScore()` の境界値テスト。

| テストケース | 入力 | 期待値 |
|------------|------|--------|
| 全項目 4 | fearMeter=4, story=4, atmosphere=4, gameplay=4, adj=0 | base=100, total=100 |
| 全項目 0 | fearMeter=0, story=0, atmosphere=0, gameplay=0, adj=0 | base=0, total=0 |
| 全項目NULL | fearMeter=null, story=null, atmosphere=null, gameplay=null | base=null, total=null |
| 怖さのみ最大 | fearMeter=4, other=null, adj=0 | base=40, total=40 |
| 調整+20でclamp | base=90, adj=+20 | total=100（100超をclamp） |
| 調整-20でclamp | base=10, adj=-20 | total=0（0未満をclamp） |
| 一部NULL | fearMeter=2, story=null, atmosphere=3, gameplay=null, adj=0 | base=35, total=35 |

### 2. バッチ処理コマンド

**`tests/Feature/Console/RecalculateReviewStatisticsCommandTest.php`**

`RecalculateFearMeterStatisticsCommandTest` と同パターン。

| テストケース | 内容 |
|------------|------|
| 初回集計 + 差分のみ再集計 | 2タイトルに固定値でレビューを登録 → 集計実行 → avg等を assert → 別タイトルに追加 → 再集計 → 更新されたタイトルのみ集計されることを確認 |
| dirty フラグ経由の再集計と件数0時のレコード削除 | 1件登録 → 集計 → 削除 + dirty 登録 → 再集計 → 統計レコードが削除されることを確認 |
| `--force-full` オプション | 前回実行時刻より古いレビューも再集計対象になることを確認 |
| is_deleted / is_hidden のレビューは統計に含まれない | 削除済みレビューのタイトルで統計が 0 件になることを確認 |

**前提条件（既存テストと同様）：**  
テスト用DB（`hgs_re3_test`）に `game_titles` が少なくとも 3 件存在すること。  
不足時は `$this->markTestSkipped(...)` でスキップ。

### 3. レビュー投稿・下書きの状態遷移

**`tests/Feature/Review/ReviewStateTransitionTest.php`**

`DatabaseTransactions` を使用。実際の DB を使った統合テスト。

| テストケース | 検証内容 |
|------------|---------|
| 新規投稿で reviews レコードが作成され version=1 のログが記録される | reviews, review_logs テーブルを直接確認 |
| 同一ユーザー + 同一タイトルで2件目の投稿は弾かれる | unique 制約 or バリデーションエラー |
| 下書き保存は reviews を変更しない | publish 前に draft を upsert して reviews.updated_at が変わらないことを確認 |
| 公開で下書きが削除される | publish 後に drafts レコードが存在しないことを確認 |
| 編集で version が加算される | 2回 publish すると review_logs に version=1, 2 が存在する |
| is_deleted=true からの再投稿で既存レコードを UPDATE して使い回す | INSERT ではなく UPDATE されることを確認 |

### 4. 怖さメーター統合

**`tests/Feature/Review/ReviewFearMeterIntegrationTest.php`**

| テストケース | 検証内容 |
|------------|---------|
| レビュー投稿時に FearMeter が未登録 → 同時作成される | user_game_title_fear_meters にレコードが作成される |
| レビュー投稿時に FearMeter が登録済み → 値が更新される | 既存レコードが UPDATE される |
| レビュー削除時に also_delete_fear_meter=true → FearMeter も削除される | user_game_title_fear_meters が削除される |
| レビュー削除時に also_delete_fear_meter=false → FearMeter は残る | user_game_title_fear_meters が残存する |

### 5. 怖さメーター直接編集（Phase 2）

**`tests/Feature/FearMeter/FearMeterEditTest.php`**

| テストケース | 検証内容 |
|------------|---------|
| 登録済みの怖さを別の値で再送信すると UPDATE され action=2 のログが記録される | |
| 値が変わらない場合は dirty フラグが登録されない | FearMeterStatisticsDirtyTitle が増えていないことを確認 |
| 値が変わった場合は dirty フラグが登録される | FearMeterStatisticsDirtyTitle が存在することを確認 |

---

## E2E テスト（Playwright）

### 必要な Test API エンドポイント（実装が必要）

既存の `api/test/create-test-account`、`api/test/fear-meter/recalculate` に倣い、以下を追加する。

| エンドポイント | 役割 |
|-------------|------|
| `POST api/test/review/recalculate` | `review:recalculate-statistics` を実行（`--force-full` も受け付ける） |
| `GET api/test/review/statistics?title_key={key}` | `game_title_review_statistics` の内容を返す |

### ファイル構成

```
tests/e2e/
  ├── fear-meter.spec.ts         # 既存（Phase 2 で修正あり）
  ├── review.spec.ts             # レビュー投稿・下書き・削除フロー
  └── review-likes-reports.spec.ts  # いいね・通報フロー
```

---

### `fear-meter.spec.ts` への追加（Phase 2 対応）

既存テストを以下の点で修正・追加する。

**修正：怖さメーター登録済みの場合にフォームが表示される**

> 現在: 「怖さメーターは編集できません。削除してから再入力してください。」のウォーニングが表示されることを確認している可能性がある  
> 変更後: 再送信で UPDATE されることを確認するテストに変更または追加する

**追加テスト：怖さメーターを直接編集できる**

```typescript
test('登録済みの怖さメーターを別の値に変更して更新できる', async ({ page, request }) => {
  // 1. テストアカウント作成 + ログイン
  // 2. Identity V の怖さメーターフォームへ
  // 3. 値 0 で登録
  // 4. 同フォームへ再アクセス → フォームが表示されていることを確認
  // 5. 別の値（例: 3）で再送信
  // 6. 成功メッセージが表示されることを確認（「怖さメーターを更新しました。」等）
});
```

---

### `review.spec.ts`

#### テスト1: 未ログイン時、レビューリンクが表示されない

```typescript
test('未ログイン時、ゲームタイトル照会画面でレビュー投稿リンクが表示されない', async ({ page }) => {
  // Identity V のタイトル詳細ページへ
  // 「レビューを書く」リンクが表示されないことを確認
});
```

#### テスト2: レビューを新規投稿して成功メッセージが表示される

```typescript
test('ログイン後、レビューを投稿して成功メッセージが表示され、タイトル詳細に反映される', async ({ page, request }) => {
  test.setTimeout(120000);

  // 1. テストアカウント作成 + ログイン（create-test-account 経由）
  // 2. Identity V のタイトル詳細ページへ
  // 3. 「レビューを書く」リンクをクリック → レビューフォームへ
  // 4. waitForTreeAppeared(page)
  // 5. プレイ状況を選択（例: cleared）
  // 6. 怖さを選択（例: 2）
  // 7. ストーリー・雰囲気・ゲーム性を選択（例: それぞれ 3）
  // 8. 本文を入力（例: 'テストレビューです。とても面白かったです。'）
  // 9. 「公開する」ボタンをクリック + POST /user/review を waitForResponse
  // 10. waitForTreeAppeared(page)
  // 11. 成功メッセージが表示されることを確認（.alert-success）
  //
  // 12. api/test/review/recalculate を叩く
  // 13. api/test/review/statistics?title_key=identity-v で集計結果を取得
  //     → 404 なら --force-full で再集計して再取得
  //
  // 14. Identity V のタイトル詳細へ戻る
  // 15. レビューセクションにレビュー件数（1件）と平均スコアが表示されることを確認
});
```

#### テスト3: 下書き保存と公開

```typescript
test('下書きを保存した後で公開できる', async ({ page, request }) => {
  test.setTimeout(120000);

  // 1. テストアカウント作成 + ログイン
  // 2. レビューフォームへ
  // 3. 本文を入力して「下書き保存」をクリック + POST /user/review/draft を waitForResponse
  // 4. waitForTreeAppeared(page)
  // 5. 成功（またはフラッシュ）メッセージが表示されることを確認
  // 6. 同フォームへ再アクセス → 下書き内容が復元されていることを確認
  // 7. 「公開する」ボタンをクリック
  // 8. 成功メッセージを確認
  // 9. フォームへ再アクセス → 「編集する」状態（既存レビューとして表示）になっていることを確認
});
```

#### テスト4: レビューを削除できる

```typescript
test('投稿したレビューをソフトデリートできる', async ({ page, request }) => {
  test.setTimeout(90000);

  // 1. テストアカウント作成 + ログイン
  // 2. レビューを投稿（最小限の入力）
  // 3. マイレビュー一覧へ → レビューが表示されることを確認
  // 4. 削除操作（削除ボタンをクリック → 確認ダイアログを確認 → DELETE /user/review を waitForResponse）
  // 5. 成功メッセージを確認
  // 6. マイレビュー一覧へ → レビューが表示されないことを確認
});
```

#### テスト5: ネタバレレビューは折りたたまれる

```typescript
test('ネタバレフラグ付きのレビューは本文が折りたたまれて表示される', async ({ page, request }) => {
  // 1. テストアカウント作成 + ログイン
  // 2. レビューフォームで has_spoiler=ON にして投稿
  // 3. タイトル詳細 or レビュー一覧ページへ
  // 4. 本文が折りたたまれていることを確認（「ネタバレを表示」ボタンが存在する等）
});
```

---

### `review-likes-reports.spec.ts`

#### テスト1: レビューにいいねできる

```typescript
test('ログイン後、他ユーザーのレビューにいいねできる', async ({ page, request }) => {
  test.setTimeout(120000);

  // 準備: A アカウントを作成してレビューを投稿（api 経由で直接作成する方が安定）
  // 1. B アカウントを作成してログイン
  // 2. Identity V のレビュー一覧ページへ
  // 3. いいねボタンをクリック + POST .../like を waitForResponse
  // 4. いいね数が 1 になることを確認
  // 5. 再度クリック（解除）→ いいね数が 0 になることを確認
});
```

#### テスト2: 未ログイン時はいいねボタンが表示されない

```typescript
test('未ログイン時、いいねボタンが表示されない', async ({ page }) => {
  // 1. Identity V のタイトル詳細またはレビュー一覧ページへ（未ログイン）
  // 2. いいねボタンが表示されないことを確認
});
```

#### テスト3: レビューを通報できる

```typescript
test('ログイン後、レビューを通報できる', async ({ page, request }) => {
  test.setTimeout(90000);

  // 1. A がレビューを投稿、B がログインして通報
  // 2. 通報フォームに理由を入力して送信 + POST .../report を waitForResponse
  // 3. 「通報しました」メッセージが表示されることを確認
  // 4. 同じレビューを再度通報しようとすると「すでに通報済み」などで弾かれることを確認
  //
  // （管理画面での通報確認は contact.spec.ts の管理画面テストと同パターンで追加可能）
});
```

---

## テスト優先度

実装フェーズに合わせて、以下の順番でテストを追加する。

| 順番 | テスト | 対応フェーズ |
|-----|-------|------------|
| 1 | `ReviewScoreCalculationTest`（スコア計算） | Phase 1 完了後 |
| 2 | `FearMeterEditTest`（怖さメーター直接編集） | Phase 2 完了後 |
| 3 | `ReviewStateTransitionTest`（状態遷移） | Phase 3 完了後 |
| 4 | `ReviewFearMeterIntegrationTest`（怖さメーター統合） | Phase 3 完了後 |
| 5 | `RecalculateReviewStatisticsCommandTest`（バッチ） | Phase 6 完了後 |
| 6 | E2E: `fear-meter.spec.ts` 追記（直接編集） | Phase 2 完了後 |
| 7 | E2E: `review.spec.ts`（投稿・下書き・削除・ネタバレ） | Phase 3〜4 完了後 |
| 8 | E2E: `review-likes-reports.spec.ts`（いいね・通報） | Phase 5 完了後 |

---

## テスト実行コマンド

```bash
# Feature テスト（特定ファイル）
php artisan test tests/Feature/Review/
php artisan test tests/Feature/Console/RecalculateReviewStatisticsCommandTest.php
php artisan test tests/Feature/FearMeter/FearMeterEditTest.php

# E2E テスト
npm run test:e2e                          # 全件
npm run test:e2e -- tests/e2e/review.spec.ts   # レビューのみ
npm run test:e2e:ui                        # UIモード（インタラクティブ）
```
