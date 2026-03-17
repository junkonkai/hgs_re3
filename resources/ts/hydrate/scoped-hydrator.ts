import { ComponentManager } from "../component-manager";

/**
 * 差し替えた DOM 範囲だけを再初期化する。
 * フォーム・コンポーネント初期化を部分更新に対応させる。
 * replaceChildren / replaceNodeById で生成されたノードは各コンストラクタでフォーム・アンカーをバインドするため、
 * ここではコンポーネント初期化のみ行う。
 */
export class ScopedHydrator
{
    /**
     * 指定ルート配下のコンポーネントを初期化する。
     * @param root 差し替え後のルート要素（components はこの部分更新用にサーバーから返されたもの）
     * @param components コンポーネント名とパラメータのマップ（省略時は未初期化）
     */
    public hydrate(root: HTMLElement, components?: { [key: string]: any | null }): void
    {
        if (!components || Object.keys(components).length === 0) {
            return;
        }
        const componentManager = ComponentManager.getInstance();
        componentManager.initializeComponents(components);
    }

    /**
     * 指定ルートに関連するリソースを解放する。
     * Phase2 では差し替え時に DOM ごと取り除かれるため、主に将来拡張用。
     */
    public dispose(_root?: HTMLElement): void
    {
        // 必要に応じてリスナー解除等を追加
    }
}
