import { test, expect } from '@playwright/test';
import { waitForTreeAppeared, createTestAccount, loginUser } from './support/utils';

/** 怖さメーターの選択肢の値（0-4） */
const FEAR_METER_VALUES = [0, 1, 2, 3, 4];

/**
 * 怖さメーターのE2Eテスト
 * 未ログイン時はゲームタイトル照会画面で「あなたの怖さメーター」リンクが表示されないことを確認する
 */
test('未ログイン時、ゲームタイトル照会画面で「あなたの怖さメーター」リンクが表示されない', async ({ page }) =>
{
  // TOPから遷移
  await page.goto('');
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // フランチャイズをクリック
  await page.getByRole('link', { name: 'フランチャイズ' }).click();
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // 「あ」をクリックしてアコーディオンを開く
  await page.getByRole('button', { name: 'あ' }).click();
  await page.waitForTimeout(500);

  // 「Identity V」をクリック（フランチャイズ詳細へ）
  await page.getByRole('link', { name: 'Identity V' }).first().click();
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // タイトルラインナップの「Identity V」をクリック（ゲームタイトル照会画面へ）
  await page.locator('#title-lineup-tree-node').getByRole('link', { name: 'Identity V' }).click();
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // ゲームタイトル照会画面に遷移したことを確認
  await expect(page.locator('#title-review-node')).toBeVisible();

  // 未ログイン状態では「あなたの怖さメーター」リンクが表示されないことを確認
  await expect(page.getByRole('link', { name: 'あなたの怖さメーター' })).not.toBeVisible();
});

/**
 * ログイン後にIdentity Vのタイトル画面へ遷移し、
 * 「あなたの怖さメーター」リンクをクリックして入力・送信し、成功メッセージを確認する
 */
test('ログイン後、怖さメーターを入力して成功メッセージが表示される', async ({ page, request }) =>
{
  test.setTimeout(90000);

  // テスト用アカウントを作成
  const createResponse = await request.post('api/test/create-test-account');
  if (!createResponse.ok()) {
    throw new Error('テスト用アカウントの作成に失敗しました。' + createResponse.status());
  }
  const { email, password } = await createResponse.json();

  // ログイン
  await page.goto('login');
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  await page.fill('#email', email);
  await page.fill('#password', password);
  const loginResponsePromise = page.waitForResponse((response) =>
    response.url().includes('/auth') && response.request().method() === 'POST'
  );
  await Promise.all([
    loginResponsePromise,
    page.getByRole('button', { name: 'ログイン' }).click(),
  ]);
  await waitForTreeAppeared(page);

  // Identity Vのタイトル画面へ遷移
  await page.goto('game/title/identity-v');
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // 「あなたの怖さメーター」リンクが存在することを確認
  const fearMeterLink = page.getByRole('link', { name: 'あなたの怖さメーター' });
  await expect(fearMeterLink).toBeVisible();

  // リンクをクリック
  await fearMeterLink.click();
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // 入力項目をランダムで選択（0-4のいずれか）
  const randomValue = FEAR_METER_VALUES[Math.floor(Math.random() * FEAR_METER_VALUES.length)];
  await page.locator(`#fear_meter_${randomValue}`).check();

  // 送信
  const submitResponsePromise = page.waitForResponse((response) =>
    response.url().includes('/fear-meter') && response.request().method() === 'POST'
  );
  await Promise.all([
    submitResponsePromise,
    page.getByRole('button', { name: '入力' }).click(),
  ]);
  await waitForTreeAppeared(page);

  // 成功メッセージが表示されることを確認
  await expect(page.locator('.alert-success')).toContainText('怖さメーターを登録しました。');

  // 再集計を実行
  const recalcResponse = await request.post('api/test/fear-meter/recalculate');
  if (!recalcResponse.ok()) {
    throw new Error('怖さメーター再集計APIの呼び出しに失敗しました。' + recalcResponse.status());
  }

  // 集計結果を取得（表示内容の検証用）
  let statisticsResponse = await request.get('api/test/fear-meter/statistics', {
    params: { title_key: 'identity-v' },
  });
  // 集計が未作成の場合は強制全件再集計して再取得
  if (statisticsResponse.status() === 404) {
    await request.post('api/test/fear-meter/recalculate', {
      data: { force_full: true },
    });
    statisticsResponse = await request.get('api/test/fear-meter/statistics', {
      params: { title_key: 'identity-v' },
    });
  }
  if (!statisticsResponse.ok()) {
    throw new Error('怖さメーター集計結果の取得に失敗しました。' + statisticsResponse.status());
  }
  const statistic = await statisticsResponse.json();

  // タイトル画面に戻る
  await page.goto('game/title/identity-v');
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // 集計結果が画面に反映されていることを確認
  const titleFearMeter = page.locator('.title-fear-meter');
  await expect(titleFearMeter).toContainText(statistic.fear_meter_text);
  await expect(titleFearMeter).toContainText(String(statistic.average_rating));
});

/**
 * 登録済みの怖さメーターを別の値に変更して更新できる（Phase 2）
 */
test('登録済みの怖さメーターを別の値に変更して更新できる', async ({ page, request }) =>
{
  test.setTimeout(90000);

  // テスト用アカウントを作成してログイン
  const account = await createTestAccount(request);
  await loginUser(page, account.email, account.password);

  // Identity V の怖さメーターフォームへ
  await page.goto('user/fear-meter/identity-v/form');
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  // 値 0 で登録（- ボタンを押して最小値 0 にしてから登録）
  // デフォルト値は 2 なので - ボタンを 2 回押す
  await page.getByRole('button', { name: '怖さメーターを下げる' }).click();
  await page.getByRole('button', { name: '怖さメーターを下げる' }).click();

  const registerPromise = page.waitForResponse(
    (r) => r.url().includes('/fear-meter') && r.request().method() === 'POST',
  );
  await Promise.all([
    registerPromise,
    page.getByRole('button', { name: '登録' }).click(),
  ]);
  await waitForTreeAppeared(page);

  // 同フォームへ再アクセス → フォームが表示されていることを確認（「更新」ボタンが存在する）
  await page.goto('user/fear-meter/identity-v/form');
  await page.waitForLoadState('networkidle');
  await waitForTreeAppeared(page);

  await expect(page.getByRole('button', { name: '更新' })).toBeVisible();

  // 別の値（3）に変更して再送信（+ ボタンを 3 回押す）
  await page.getByRole('button', { name: '怖さメーターを上げる' }).click();
  await page.getByRole('button', { name: '怖さメーターを上げる' }).click();
  await page.getByRole('button', { name: '怖さメーターを上げる' }).click();

  const updatePromise = page.waitForResponse(
    (r) => r.url().includes('/fear-meter') && r.request().method() === 'POST',
  );
  await Promise.all([
    updatePromise,
    page.getByRole('button', { name: '更新' }).click(),
  ]);
  await waitForTreeAppeared(page);

  // 成功メッセージが表示されることを確認
  await expect(page.locator('.alert-success')).toContainText('怖さメーターを保存しました。');
});
