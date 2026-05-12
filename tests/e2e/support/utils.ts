import { APIRequestContext, Page } from '@playwright/test';

/**
 * HGN のツリーノードが出現アニメーションを完了するまで待機する
 */
export const waitForTreeAppeared = async (page: Page): Promise<void> =>
{
  await new Promise(resolve => setTimeout(resolve, 2000));
  await page.waitForFunction(() =>
    (window as any)?.hgn?.currentNode?.nodeContentTree?.appearStatus === 2
  );
};

/**
 * テスト用アカウントを作成する
 */
export const createTestAccount = async (request: APIRequestContext): Promise<{ email: string; password: string }> =>
{
  const response = await request.post('api/test/create-test-account');
  if (!response.ok()) {
    throw new Error('テスト用アカウントの作成に失敗しました。' + response.status());
  }
  return response.json();
};

/**
 * ログインする（ログインページへ遷移してフォーム入力・送信）
 */
export const loginUser = async (page: Page, email: string, password: string): Promise<void> =>
{
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
};

