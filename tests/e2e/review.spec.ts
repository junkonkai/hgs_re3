import { test, expect } from '@playwright/test';
import { waitForTreeAppeared, createTestAccount, loginUser } from './support/utils';

/** Identity V のタイトルキー */
const TITLE_KEY = 'identity-v';

/**
 * レビューフォームへ直接遷移してから必須項目を入力し、公開する
 * 呼び出し元はログイン済みであること
 */
const fillAndPublishReview = async (
  page: Parameters<typeof loginUser>[0],
  body: string = 'テストレビューです。とても面白かったです。',
): Promise<void> =>
{
  await page.goto(`user/review/${TITLE_KEY}/form`);
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // プレイ状況を選択
  await page.check('input[name="play_status"][value="cleared"]');

  // 本文を入力
  await page.fill('#body', body);

  // 公開する
  const publishPromise = page.waitForResponse(
    (r) =>
      r.url().includes('/user/review') &&
      !r.url().includes('/draft') &&
      !r.url().includes('/form') &&
      r.request().method() === 'POST',
  );
  await Promise.all([
    publishPromise,
    page.getByRole('button', { name: '公開する' }).click(),
  ]);
  await waitForTreeAppeared(page);
};

// ---------------------------------------------------------------------------

/**
 * 未ログイン時、ゲームタイトル照会画面でレビュー投稿リンクが表示されない
 */
test('未ログイン時、ゲームタイトル照会画面でレビュー投稿リンクが表示されない', async ({ page }) =>
{
  await page.goto(`game/title/${TITLE_KEY}`);
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // 「レビューを書く」リンクが表示されないことを確認
  await expect(page.getByRole('link', { name: 'レビューを書く' })).not.toBeVisible();
});

// ---------------------------------------------------------------------------

/**
 * ログイン後、レビューを投稿して成功メッセージが表示され、タイトル詳細に反映される
 */
test('ログイン後、レビューを投稿して成功メッセージが表示され、タイトル詳細に反映される', async ({ page, request }) =>
{
  test.setTimeout(120000);

  const jsErrors: string[] = [];
  page.on('pageerror', (err) => jsErrors.push(err.message));

  // テスト用アカウントを作成してログイン
  const account = await createTestAccount(request);
  await loginUser(page, account.email, account.password);

  // Identity V のタイトル詳細ページへ
  await page.goto(`game/title/${TITLE_KEY}`);
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // 「レビューを書く」リンクをクリック → レビューフォームへ
  await page.getByRole('link', { name: 'レビューを書く' }).first().click();
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // プレイ状況を選択
  await page.check('input[name="play_status"][value="cleared"]');

  // 本文を入力
  await page.fill('#body', 'テストレビューです。とても面白かったです。');

  // 「公開する」ボタンをクリック
  const publishPromise = page.waitForResponse(
    (r) =>
      r.url().includes('/user/review') &&
      !r.url().includes('/draft') &&
      !r.url().includes('/form') &&
      r.request().method() === 'POST',
  );
  await Promise.all([
    publishPromise,
    page.getByRole('button', { name: '公開する' }).click(),
  ]);
  await waitForTreeAppeared(page);

  // 成功メッセージが表示されることを確認（マイレビュー一覧にリダイレクト）
  await expect(page.locator('.alert-success')).toContainText('レビューを公開しました。');

  // 再集計を実行
  const recalcResponse = await request.post('api/test/review/recalculate');
  if (!recalcResponse.ok()) {
    throw new Error('レビュー再集計APIの呼び出しに失敗しました。' + recalcResponse.status());
  }

  // 集計結果を取得
  let statisticsResponse = await request.get('api/test/review/statistics', {
    params: { title_key: TITLE_KEY },
  });
  // 集計が未作成の場合は強制全件再集計して再取得
  if (statisticsResponse.status() === 404) {
    await request.post('api/test/review/recalculate', {
      data: { force_full: true },
    });
    statisticsResponse = await request.get('api/test/review/statistics', {
      params: { title_key: TITLE_KEY },
    });
  }
  if (!statisticsResponse.ok()) {
    throw new Error('レビュー集計結果の取得に失敗しました。' + statisticsResponse.status());
  }
  const statistic = await statisticsResponse.json();
  expect(statistic.review_count).toBeGreaterThanOrEqual(1);

  // Identity V のタイトル詳細へ戻る
  await page.goto(`game/title/${TITLE_KEY}`);
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // レビューセクションに件数が表示されることを確認
  const reviewsNode = page.locator('#title-reviews-node');
  await expect(reviewsNode).toContainText('件');
  // 「レビューはまだないようだ」が表示されていないことを確認
  await expect(reviewsNode).not.toContainText('まだない');

  expect(jsErrors).toHaveLength(0);
});

// ---------------------------------------------------------------------------

/**
 * 下書きを保存した後で公開できる
 */
test('下書きを保存した後で公開できる', async ({ page, request }) =>
{
  test.setTimeout(120000);

  const account = await createTestAccount(request);
  await loginUser(page, account.email, account.password);

  // レビューフォームへ直接遷移
  await page.goto(`user/review/${TITLE_KEY}/form`);
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  const draftBody = '下書きのテスト本文です。まだ公開していません。';

  // 本文を入力して「下書き保存」をクリック
  await page.fill('#body', draftBody);
  const draftPromise = page.waitForResponse(
    (r) => r.url().includes('/user/review/draft') && r.request().method() === 'POST',
  );
  await Promise.all([
    draftPromise,
    page.getByRole('button', { name: '下書き保存' }).click(),
  ]);
  await waitForTreeAppeared(page);

  // 成功メッセージが表示されることを確認（フォームにリダイレクト）
  await expect(page.locator('.alert-success')).toContainText('下書きを保存しました。');

  // 下書き内容が復元されていることを確認
  await expect(page.locator('#body')).toHaveValue(draftBody);

  // プレイ状況を選択して公開する
  await page.check('input[name="play_status"][value="cleared"]');

  const publishPromise = page.waitForResponse(
    (r) =>
      r.url().includes('/user/review') &&
      !r.url().includes('/draft') &&
      !r.url().includes('/form') &&
      r.request().method() === 'POST',
  );
  await Promise.all([
    publishPromise,
    page.getByRole('button', { name: '公開する' }).click(),
  ]);
  await waitForTreeAppeared(page);

  // 公開成功メッセージを確認
  await expect(page.locator('.alert-success')).toContainText('レビューを公開しました。');

  // フォームへ再アクセス → 既存レビューとして表示（削除ボタンが存在する）
  await page.goto(`user/review/${TITLE_KEY}/form`);
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  await expect(page.getByRole('button', { name: 'レビューを削除' })).toBeVisible();
});

// ---------------------------------------------------------------------------

/**
 * 投稿したレビューをソフトデリートできる
 */
test('投稿したレビューをソフトデリートできる', async ({ page, request }) =>
{
  test.setTimeout(90000);

  const account = await createTestAccount(request);
  await loginUser(page, account.email, account.password);

  // レビューを投稿
  await fillAndPublishReview(page);

  // マイレビュー一覧へ → レビューが表示されることを確認（Identity V の行が存在する）
  await page.goto('user/review');
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  await expect(page.getByRole('link', { name: 'Identity V' }).first()).toBeVisible();

  // フォームページへ移動して削除操作
  await page.goto(`user/review/${TITLE_KEY}/form`);
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // 確認ダイアログを全て承諾する
  page.on('dialog', (dialog) => dialog.accept());

  const deletePromise = page.waitForResponse(
    (r) =>
      r.url().includes('/user/review') &&
      !r.url().includes('/draft') &&
      !r.url().includes('/form') &&
      r.request().method() === 'POST', // Laravel の _method=DELETE は POST として送信される
  );
  await Promise.all([
    deletePromise,
    page.getByRole('button', { name: 'レビューを削除' }).click(),
  ]);
  await waitForTreeAppeared(page);

  // 成功メッセージを確認
  await expect(page.locator('.alert-success')).toContainText('レビューを削除しました。');

  // マイレビュー一覧へ → レビューが表示されないことを確認
  await page.goto('user/review');
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // 一覧テーブルが存在しない（レビューなし状態）かつ Identity V のリンクが消えている
  await expect(page.locator('#review-list-node')).not.toBeVisible();
});

// ---------------------------------------------------------------------------

/**
 * ネタバレフラグ付きのレビューは本文が折りたたまれて表示される
 */
test('ネタバレフラグ付きのレビューは本文が折りたたまれて表示される', async ({ page, request }) =>
{
  test.setTimeout(90000);

  const account = await createTestAccount(request);
  await loginUser(page, account.email, account.password);

  // レビューフォームへ
  await page.goto(`user/review/${TITLE_KEY}/form`);
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // プレイ状況・本文・ネタバレフラグを入力
  await page.check('input[name="play_status"][value="cleared"]');
  await page.fill('#body', 'これはネタバレを含むレビューです。ラスボスは○○でした。');
  await page.check('input[name="has_spoiler"]');

  // 公開する
  const publishPromise = page.waitForResponse(
    (r) =>
      r.url().includes('/user/review') &&
      !r.url().includes('/draft') &&
      !r.url().includes('/form') &&
      r.request().method() === 'POST',
  );
  await Promise.all([
    publishPromise,
    page.getByRole('button', { name: '公開する' }).click(),
  ]);
  await waitForTreeAppeared(page);

  await expect(page.locator('.alert-success')).toContainText('レビューを公開しました。');

  // タイトルのレビュー一覧ページへ（統計不要でレビューが直接表示される）
  await page.goto(`game/title/${TITLE_KEY}/reviews`);
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // ネタバレ折りたたみ要素（<details>）と「本文を表示」サマリーが存在することを確認
  await expect(page.locator('details')).toBeVisible();
  await expect(page.locator('details summary')).toContainText('本文を表示（ネタバレあり）');
});
