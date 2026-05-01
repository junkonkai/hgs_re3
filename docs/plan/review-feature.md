# レビュー機能 実装計画

## 概要

ユーザーがゲームタイトルに対してレビューを投稿できる機能。
怖さメーターの仕組みを流用・共有しながら、より詳細な評価を記録できるようにする。

---

## 評価項目

### プレイ時間（任意）

選択肢形式で入力する。

```php
enum PlayTime: string
{
    case Under1Hour   = 'under_1h';    // 1時間未満
    case Hour1To3     = '1h_to_3h';   // 1〜3時間
    case Hour3To5     = '3h_to_5h';   // 3〜5時間
    case Hour5To10    = '5h_to_10h';  // 5〜10時間
    case Hour10To20   = '10h_to_20h'; // 10〜20時間
    case Over20Hours  = 'over_20h';   // 20時間以上
}
```

### プレイ環境（任意、複数選択可）

そのゲームタイトルに紐づく `GamePackage`（`GameTitlePackageLink` 経由）から選択する。
例：「PS5版」「Steam版」「Switch版」など。
複数選択可（PS版と Xbox版両方プレイ済み、など）。
`user_game_title_review_packages` テーブルで管理する（後述）。

### ホラー種別タグ（任意、複数選択可）

怖さの内訳・種類を伝えるタグ。`review_horror_type_tags` テーブルで管理する（後述）。

```php
enum HorrorTypeTag: string
{
    case JumpScare    = 'jump_scare';    // ジャンプスケア
    case Psychological = 'psychological'; // 心理的恐怖
    case Gore         = 'gore';          // グロテスク描写
    case Atmosphere   = 'atmosphere';    // 雰囲気・サスペンス
    case Supernatural = 'supernatural';  // 超自然現象
    case Enclosed     = 'enclosed';      // 閉所・暗所
    case Chased       = 'chased';        // 追いかけられる系
}
```

### 入力軸（任意、すべて 0〜4）

| 軸 | 説明 | スケール | 係数 | 最大寄与 |
|----|------|---------|------|---------|
| 怖さ | **怖さメーターと統合** | 0〜4（FearMeter enum） | ×10 | 40点 |
| ストーリー | 物語・シナリオの評価 | 0〜4 | ×5 | 20点 |
| 雰囲気・演出 | BGM・映像・ライティング等のホラー演出 | 0〜4 | ×5 | 20点 |
| ゲーム性 | ジャンルを問わずゲームとしての面白さ | 0〜4 | ×5 | 20点 |

### 総合評価（0〜100点）

#### ステップ1: ベーススコア算出

```
ベーススコア = 怖さ×10 + ストーリー×5 + 雰囲気・演出×5 + ゲーム性×5
最大 = 4×10 + 4×5 + 4×5 + 4×5 = 40 + 20 + 20 + 20 = 100点
```

未入力の軸は 0 として計算する（0点寄与）。

#### ステップ2: ユーザー調整

ユーザーが **−20〜+20 の範囲**で点数を加減できる。

```
最終スコア = clamp(ベーススコア + ユーザー調整, 0, 100)
```

#### 計算例

| 怖さ | ストーリー | 雰囲気 | ゲーム性 | ベース | 調整 | 最終 |
|------|---------|------|---------|------|------|------|
| 4 | 4 | 4 | 4 | 100 | 0 | **100** |
| 2 | 4 | 4 | 4 | 80 | +20 | **100** |
| 0 | 4 | 4 | 4 | 60 | +20 | **80** |
| 2 | 2 | 2 | 2 | 40 | +20 | **60** |
| 4 | 4 | 4 | 4 | 100 | −20 | **80** |

> 怖さ=2 でも他軸がすべて最高値ならベース80点。ユーザー調整+20で100点到達可能。

---

## プレイ状況（enum）

```php
enum PlayStatus: string
{
    case Cleared = 'cleared';  // クリア済み
    case Playing = 'playing';  // プレイ中（未クリア）
    case Watched = 'watched';  // 配信・動画で視聴済み
}
```

「視聴済み」を選んだレビューには「配信・動画での視聴に基づくレビュー」バッジを表示する。

---

## その他のフィールド

| フィールド | 型 | 必須 | 備考 |
|-----------|-----|------|------|
| 本文 | text | 必須 | 上限 2000 文字 |
| ネタバレフラグ | bool | 必須 | ON の場合、本文を折りたたみ表示 |

---

## 制約

### 件数
- 1 ユーザー × 1 タイトル × 1 件まで（下書きも同様）
- 未プレイ・未視聴ユーザーは投稿不可（プレイ状況の選択肢で担保）

### 編集
- 回数制限なし（Steam・Amazon 等の主要サービスに準拠）
- 編集のたびにスナップショットをログに保存（バージョン管理。後述）
- 統計への反映はバッチ処理のため、変更後すぐには反映されない

### 怖さメーターの編集（既存機能への変更）

現在の「削除→再入力」方式から、**直接編集可能**に変更する。

#### Controller 変更方針

- `FearMeterController::store` を upsert に変更（新規登録・上書き更新を同一エンドポイントで処理）
- `FearMeterController::destroy` は引き続き残す

#### フォーム UI 変更

- 登録済みの場合も入力フォームを表示し、現在値を初期値として表示する
- 削除ボタンも引き続き表示する
  - **レビューが存在しない場合**：「削除します。よろしいですか？」の確認のみ
  - **レビューが存在する場合**：「怖さメーターを削除すると、レビューも一緒に削除されます。よろしいですか？」と警告し、OKであればレビュー（ソフトデリート）と怖さメーターを両方削除する

#### ログ記録（`UserGameTitleFearMeterLog`）

`action` カラムを追加し、操作種別を数値で記録する。

| 値 | 意味 |
|----|------|
| 1 | 新規登録 |
| 2 | 編集 |
| 3 | 削除 |

`is_deleted` カラムは削除操作時（action=3）のみ `true` にする。既存の意味を維持する。

#### `FearMeterStatisticsDirtyTitle` の登録タイミング

- 既存：削除時のみ
- 変更後：新規登録・削除、および**編集時に値が変わった場合**のみ登録する（前回と同じ値での保存は登録しない）

#### 統計への反映

バッチ処理で行う（変更後すぐには反映されない）。

---

## 下書き機能

下書きは `user_game_title_review_drafts` テーブルに保存する。
公開済みレビューは `user_game_title_reviews` のまま維持され、下書き保存中も引き続き公開状態が保たれる。

### `user_game_title_review_drafts`（下書き）

| カラム | 型 | 備考 |
|-------|----|------|
| id | bigint | PK |
| user_id | bigint | FK |
| game_title_id | bigint | FK |
| review_id | bigint null | FK。NULL = 新規投稿の下書き、値あり = 編集中の下書き |
| play_status | string | |
| body | text | |
| has_spoiler | bool | |
| score_story | tinyint null | 0〜4 |
| score_atmosphere | tinyint null | 0〜4 |
| score_gameplay | tinyint null | 0〜4 |
| user_score_adjustment | smallint null | −20〜+20 |
| created_at / updated_at | timestamp | |

`user_id + game_title_id` にユニーク制約（1ユーザー1タイトルにつき下書きは1件まで）。

### `user_game_title_review_draft_packages`（下書き用プレイ環境）

| カラム | 型 | 備考 |
|-------|----|------|
| id | bigint | PK |
| draft_id | bigint | FK（`user_game_title_review_drafts`） |
| game_package_id | bigint | FK（GamePackage） |

`draft_id + game_package_id` にユニーク制約。

### `user_game_title_review_draft_horror_type_tags`（下書き用ホラー種別タグ）

| カラム | 型 | 備考 |
|-------|----|------|
| id | bigint | PK |
| draft_id | bigint | FK（`user_game_title_review_drafts`） |
| tag | string | HorrorTypeTag enum |

`draft_id + tag` にユニーク制約。

### 状態遷移とフロー

| 操作 | 挙動 |
|------|------|
| 下書き保存（何度でも） | `review_drafts` を upsert。ログ記録なし |
| 新規公開（初回） | `review_drafts` の内容で `reviews` を INSERT → ログ記録（version 1）→ `review_drafts` を削除 |
| 公開済みレビューの編集開始 | `reviews` の現在の内容を `review_drafts`（`review_id` 付き）に複製 |
| 編集下書きの保存 | `review_drafts` を更新。`reviews` は変更しない（公開状態を維持） |
| 編集下書きの公開 | `reviews` を UPDATE → ログ記録（version を加算）→ `review_drafts` を削除 |
| 下書きを破棄 | `review_drafts` を削除 |

### 下書きの削除タイミング

- 公開時（新規・編集とも）に自動削除
- ユーザーがレビューを削除したとき
- ユーザーが退会（物理削除）したとき
- 下書き破棄操作をしたとき

---

## 怖さメーターとの統合

レビューの「怖さ」評価は `UserGameTitleFearMeter` と同一データとして扱う。
レビューテーブルには怖さスコアを持たず、`UserGameTitleFearMeter` を参照する。

**制約：レビューが存在する場合、怖さメーターは必須。**  
怖さメーター単体での生存は可能だが、レビューがあって怖さメーターがない状態は作れない。

| ケース | 挙動 |
|--------|------|
| レビュー新規投稿 | 怖さメーターは**必須入力**。未登録なら同時作成、登録済みなら現在値を初期値として表示・変更可 |
| レビュー編集で怖さを変更 | `UserGameTitleFearMeter` も更新 |
| 怖さメーター単体投稿 | 引き続き可（レビューなしで怖さのみ登録できる） |
| 怖さメーター単体編集 | 可（回数制限なし） |
| 怖さメーター削除（レビューなし） | 確認後、怖さメーターのみ削除 |
| 怖さメーター削除（レビューあり） | 「レビューも一緒に削除されます」と警告。OKならレビュー（ソフトデリート）＋怖さメーターを削除 |
| レビュー削除 | 怖さメーターを一緒に削除するか**ユーザーに確認**して選択に委ねる（怖さメーターのみ残すことは可能） |

---

## バージョン管理（ログ）

公開済みレビューの編集履歴をすべてスナップショットとして保持する。
各スナップショットには連番の `version` を付与し、通報・いいねからその時点の内容を参照できる。

### `user_game_title_review_logs`（スナップショット）

| カラム | 型 | 備考 |
|-------|----|------|
| id | bigint | PK（バージョン ID として使用） |
| review_id | bigint | FK |
| user_id | bigint | FK |
| version | int | 1 から始まるレビューごとの連番 |
| play_status | string | このバージョン時点の値 |
| play_time | string null | PlayTime enum |
| game_package_ids | json null | GamePackage id の配列（スナップショット用） |
| body | text | このバージョン時点の本文 |
| has_spoiler | bool | |
| score_story | tinyint null | 0〜4 |
| score_atmosphere | tinyint null | 0〜4 |
| score_gameplay | tinyint null | 0〜4 |
| user_score_adjustment | smallint null | −20〜+20 |
| base_score | tinyint null | 0〜100 |
| total_score | tinyint null | 0〜100 |
| horror_type_tags | json null | HorrorTypeTag enum の配列（スナップショット用） |
| created_at | timestamp | 編集日時 |

- 新規公開時（version 1）も記録する
- 下書き保存時は記録しない
- `review_id + version` にユニーク制約

---

## いいね機能

怖さメーターのコメントいいねとは**別テーブル**で管理する。

### `user_game_title_review_likes`

| カラム | 型 | 備考 |
|-------|----|------|
| id | bigint | PK |
| user_id | bigint | FK |
| review_id | bigint | FK |
| review_log_id | bigint | FK（いいねした時点のバージョン） |
| created_at | timestamp | |

`user_id + review_id` にユニーク制約（1ユーザー1レビュー1いいね）。

- **ログイン必須**。未ログインユーザーにはいいねボタンを表示しない（またはログインを促す）

### いいね数の集計

| 種別 | 定義 |
|------|------|
| 累計いいね数 | `review_id` でカウントした全件数 |
| 現バージョンのいいね数 | `review_log_id` = 最新バージョンの id でカウント |

表示例：「★ 12 いいね（現在のバージョン: 3）」

### いいね履歴ページ（マイノード配下）

自分がいいねしたレビューの一覧を表示するページ。

**変更検知：**
`like.review_log_id` と `review.current_log_id` を比較し、一致しない場合は「いいねした後に内容が更新されています」と表示する。

| 状態 | 表示 |
|------|------|
| いいね時と内容が同じ | 通常表示 |
| いいね後に内容が変更された | レビュー本文の上部に「いいねした後に内容が更新されています」と表示 |
| レビューが削除された（ソフトデリート） | 「このレビューは削除されました」と表示 |
| レビューが非表示（管理者） | 「このレビューは非表示です」と表示 |

- ソート：いいねした日時の新しい順
- ページネーション：怖さメーター一覧などに合わせる（未定）

---

## 通報機能

怖さメーターのコメント通報とは**別テーブル**で管理する。
管理画面での対応フローは怖さメーター通報と同様の設計とする。

### `user_game_title_review_reports`

| カラム | 型 | 備考 |
|-------|----|------|
| id | bigint | PK |
| user_id | bigint | FK（通報者） |
| review_id | bigint | FK |
| review_log_id | bigint | FK（**通報時点のバージョン**） |
| reason | text null | 通報理由 |
| is_resolved | bool | 対応済みフラグ |
| resolved_by_admin_id | bigint null | 対応した管理者 |
| resolved_at | timestamp null | |
| created_at | timestamp | |

- **ログイン必須**。未ログインユーザーには通報リンクを表示しない

管理画面では `review_log_id` を通じて**通報時点の本文・評価内容**を表示できる。
現在のレビュー内容と比較表示することも可能。

---

## タイトル詳細ページへの表示

```
[怖さメーター]  ← 既存。統計は変わらず UserGameTitleFearMeter から集計
[レビュー]
  ├─ 総合評価: ★4.2（12件）
  ├─ 軸別平均: ストーリー 4.0 / 雰囲気・演出 3.8 / ゲーム性 4.1
  ├─ 新着レビュー 3件（ネタバレなし優先）
  └─ [レビューを全件見る →]（別ページ）
```

### 全件表示ページ
- ソート：新しい順（固定）
- ページネーション：10件 / ページ（件数は調整可）

---

## データモデル（案）

### `user_game_title_reviews`（レビュー本体）

| カラム | 型 | 備考 |
|-------|----|------|
| id | bigint | PK |
| user_id | bigint | FK |
| game_title_id | bigint | FK |
| is_hidden | bool | 管理者による非表示フラグ |
| hidden_by_admin_id | bigint null | 非表示にした管理者 |
| hidden_at | timestamp null | |
| play_status | string | enum: cleared / playing / watched |
| body | text | 本文（〜2000文字）|
| has_spoiler | bool | ネタバレフラグ |
| play_time | string null | PlayTime enum |
| score_story | tinyint null | 0〜4 |
| score_atmosphere | tinyint null | 0〜4 |
| score_gameplay | tinyint null | 0〜4 |
| user_score_adjustment | smallint null | −20〜+20（ユーザー調整） |
| base_score | tinyint null | 0〜100（軸の計算結果） |
| total_score | tinyint null | 0〜100（base_score + user_score_adjustment をclamp） |
| current_log_id | bigint null | 現在の公開バージョンの log id |
| ogp_image_path | string null | `public/img/review/{reviews.idをハッシュ化}.png` |
| is_deleted | bool | ユーザーによるソフトデリートフラグ |
| created_at / updated_at | timestamp | |

`user_id + game_title_id` にユニーク制約。

### `user_game_title_review_packages`（プレイ環境）

| カラム | 型 | 備考 |
|-------|----|------|
| id | bigint | PK |
| review_id | bigint | FK |
| game_package_id | bigint | FK（GamePackage） |

`review_id + game_package_id` にユニーク制約。

### `user_game_title_review_horror_type_tags`（ホラー種別タグ）

| カラム | 型 | 備考 |
|-------|----|------|
| id | bigint | PK |
| review_id | bigint | FK |
| tag | string | HorrorTypeTag enum |

`review_id + tag` にユニーク制約。

### `user_game_title_review_logs`（スナップショット）

上記「バージョン管理」セクション参照。

### `user_game_title_review_likes`（いいね）

上記「いいね機能」セクション参照。

### `user_game_title_review_reports`（通報）

上記「通報機能」セクション参照。

### `game_title_review_statistics`（統計キャッシュ）

| カラム | 型 | 備考 |
|-------|----|------|
| game_title_id | bigint | PK |
| review_count | int | 公開済みのみカウント |
| avg_total_score | decimal(5,2) null | 0〜100の平均 |
| avg_story | decimal(4,2) null | 0〜4の平均 |
| avg_atmosphere | decimal(4,2) null | 0〜4の平均 |
| avg_gameplay | decimal(4,2) null | 0〜4の平均 |
| updated_at | timestamp | |

怖さメーター統計（`game_title_fear_meter_statistics`）とは**別テーブル**で管理。
統計の更新はバッチ処理で行う（怖さメーターの `RecalculateFearMeterStatisticsCommand` と同様の仕組み）。

---

## ユーザーによるレビュー削除

- `is_deleted = true` にするソフトデリート（公開側には表示されなくなる）
- 削除後もログ・いいね・通報レコードはデータとして残す（管理側で参照可能）
- 削除したレビューは再投稿可能。再投稿時は既存レコード（`is_deleted = true`）を UPDATE して再利用する（新規 INSERT しない）
- 下書きが存在する場合は同時に削除する

### 怖さメーターの扱い

削除確認時に「怖さメーターも一緒に削除しますか？」をユーザーに問い、選択に委ねる。

| ユーザーの選択 | 挙動 |
|-------------|------|
| レビューのみ削除 | `reviews` をソフトデリート。怖さメーターはそのまま残る |
| レビューと怖さメーターを両方削除 | `reviews` をソフトデリート + `UserGameTitleFearMeter` を削除 |

---

## 管理画面からの操作

### 非表示（`is_hidden` フラグ）

- 公開側の表示：レビュー本文の代わりに「このレビューは非表示です」と表示
- いいねボタンは非表示中は出さない
- 非表示を解除すれば元に戻る（ソフト操作）

### 削除（管理者による物理削除）

- レビュー本体・ログ・いいね・通報レコードをすべて**物理削除**
- 怖さメーターは削除しない

---

## 退会時のデータ削除

既存フロー：退会操作で `withdrawn_at` を付与 → 100日後に `user:purge-withdrawn` バッチで `$user->delete()` を実行。

レビュー関連データは `User` モデルの `deleting` イベント、または外部キー制約の `CASCADE ON DELETE` でまとめて削除する。

### 削除対象

| テーブル | 削除方法 |
|---------|---------|
| `user_game_title_review_drafts` | ユーザー削除に連動 |
| `user_game_title_reviews` | ユーザー削除に連動 |
| `user_game_title_review_logs` | review 削除に連動（CASCADE） |
| `user_game_title_review_likes` | ユーザー削除に連動（自分のいいね） |
| `user_game_title_review_reports` | ユーザー削除に連動（自分の通報） |
| `user_game_title_fear_meters` | 既存の削除処理に準ずる |
| `user_game_title_fear_meter_logs` | 既存の削除処理に準ずる |

> 他ユーザーのレビューへの「いいね」も削除する（`review_likes.user_id` が削除ユーザー）。
> 統計キャッシュ（`game_title_review_statistics`）はバッチで再集計する（怖さメーターの `RecalculateFearMeterStatisticsCommand` と同様の仕組みで実装）。

---

## SNS 共有

### レビュー個別URL

```
/game/title/{titleKey}/review/{show_id}
```

`show_id` は `users.show_id`（プロフィール設定で変更可能なID）を使用する。  
show_id を変更するとレビュー URL が変わるため、以下の3箇所で注意を表示する。

| 表示箇所 | 注意文（例） |
|---------|------------|
| ユーザー登録時 | 「このIDはレビューURLに使用されます。後から変更できますが、変更すると過去に共有したURLが使えなくなります」 |
| show_id 変更画面 | 「変更すると、これまで共有したレビューのURLがすべて変わります（旧URLはアクセスできなくなります）」 |
| レビュー入力画面 | 「レビューのURLにあなたのユーザーIDが含まれます。IDを変更するとこのURLも変わります」 |

X（Twitter）等の SNS でも同様の仕様（ユーザー名変更で旧URLが切れる）のため、ユーザー責任の範囲として許容する。

### 共有ボタン

X（Twitter）、LINE などへの共有ボタンを設ける。

### OGP 画像生成

OGP画像はサーバーサイドで生成する（クライアント側生成は不採用 — ユーザーが任意の画像を送れるためセキュリティリスクあり）。

**実装方針：Rust マイクロサービス**

独立したRustサービスとしてHTTPエンドポイントを実装する。PHPから内部HTTPリクエストでパラメーターを渡し、PNGを受け取る。

| ライブラリ | 役割 |
|-----------|------|
| `axum` | HTTPサーバー |
| `resvg` + `tiny-skia` | SVG → PNG レンダリング |
| `ab_glyph` | フォント読み込み・テキスト描画 |

SVGテンプレートにスコア等の値を埋め込み、`resvg` でPNGにレンダリングする。

**処理フロー（キュー方式）：**

```
レビュー公開 → キューに OGP 画像生成ジョブを登録
                    ↓
        Rust サービスがジョブを処理して PNG 生成・保存
                    ↓
        DB に OGP 画像のパスを記録

表示側:
  - 画像生成済み → 生成済み画像の URL を OGP メタタグに出力
  - 未生成     → 仮画像（サイト共通デフォルト画像）を OGP メタタグに出力
```

生成完了前に SNS シェアされた場合は仮画像がキャッシュされる可能性があるが、実際のシェアはレビュー投稿後しばらく経ってから行われることが多いため実用上の問題は少ない。

**OGP テキスト：**

- title: `{ゲームタイトル名} のレビュー — {show_id}`
- description: `総合{N}点 / 怖さ {N}/4 / ストーリー {N}/4 / 雰囲気・演出 {N}/4 / ゲーム性 {N}/4`
  - `has_spoiler = true` の場合は先頭に「【ネタバレあり】」を付与
  - 未入力の軸（NULL）は description に含めない

---

## バッチ処理

怖さメーターの `RecalculateFearMeterStatisticsCommand` と同じパターンで実装する。
**数時間おきに1回**スケジュール実行する。

### コマンド

```
review:recalculate-statistics
```

オプション：
- `--force-full` : 前回実行時刻に関わらず全件再集計
- `--no-progress` : プログレスバー非表示（API 等からの呼び出し用）

### 処理対象の絞り込み

怖さメーターと同様に「ダーティフラグ」方式を採用し、変更のあったタイトルのみ再集計する。

| テーブル | 役割 |
|---------|------|
| `review_statistics_dirty_titles` | 再集計が必要なタイトルを記録 |
| `review_statistics_run_log` | 最終実行時刻を記録 |

**ダーティフラグの登録タイミング：**
- レビューの新規公開・編集・削除時
- ユーザー退会によるレビュー削除時
- 管理者によるレビュー削除・非表示操作時

### 集計内容

`game_title_review_statistics` を更新する。

```
review_count    ← is_deleted=false かつ is_hidden=false の公開済みレビュー件数
avg_total_score ← total_score の平均
avg_story       ← score_story の平均（NULL除外）
avg_atmosphere  ← score_atmosphere の平均（NULL除外）
avg_gameplay    ← score_gameplay の平均（NULL除外）
```

対象タイトルのレビューが 0 件になった場合は統計レコードを削除する（怖さメーターと同様）。

---

## Discord 通知（通報時）

通報が投稿された際に `DiscordChannel::Contact` へ通知を送る。

```php
app(DiscordWebhookService::class)
    ->to(DiscordChannel::Contact)
    ->username('HGN 通報Bot')
    ->send("レビューへの通報がありました。\nタイトル: {$title->name}\n通報者ID: {$reporter->id}\n管理画面で確認してください。");
```

通知内容（案）：
- ゲームタイトル名
- 通報者のユーザーID（名前は出さない）
- 管理画面の通報詳細への URL

---

## 今後の対応予定

### ユーザー名変更時のOGP画像一括再生成

**概要：**
ユーザーが `users.name`（表示名）を変更したとき、そのユーザーが投稿したレビューのOGP画像（`ogp_image_path` が設定済みのもの）を再生成する。

**方針：**
- 一度に全件処理するのではなく、`GenerateReviewOgpImage` ジョブをキューに積んで順次処理する。
- ユーザー名変更処理（コントローラ or サービス）の完了後にジョブをディスパッチする。

**実装イメージ：**

```php
// ユーザー名変更後
$user->update(['name' => $newName]);

// OGP画像が生成済みのレビューのみキューに積む
UserGameTitleReview::where('user_id', $user->id)
    ->whereNotNull('ogp_image_path')
    ->where('is_deleted', false)
    ->pluck('id')
    ->each(fn($id) => GenerateReviewOgpImage::dispatch($id));
```

**注意：**
- ジョブ数が多い場合でもキューで吸収されるため、レスポンスには影響しない。
- OGP画像の実ファイルは上書きされる（ファイル名は `review_id` ベースのため同名で上書き）。
