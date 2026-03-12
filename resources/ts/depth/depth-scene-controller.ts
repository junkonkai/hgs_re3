import type { DepthMode } from "./depth-types";

/**
 * Phase5: シーン全体の Z 演出モードを制御する。
 * ルート要素（#current-node）に perspective 用クラスを付与する。
 */
export class DepthSceneController
{
    private _mode: DepthMode = 'transition';
    private _rootElement: HTMLElement;
    private _focusedNodeId: string | null = null;

    public constructor(rootElement: HTMLElement)
    {
        this._rootElement = rootElement;
    }

    public setMode(mode: DepthMode): void
    {
        this._mode = mode;
        if (mode === 'none') {
            this._rootElement.classList.remove('tree-depth-root');
        } else {
            this._rootElement.classList.add('tree-depth-root');
        }
    }

    public get mode(): DepthMode
    {
        return this._mode;
    }

    /**
     * persistent 用: フォーカスノードを記憶する
     */
    public focusNode(nodeId: string): void
    {
        this._focusedNodeId = nodeId;
    }

    public get focusedNodeId(): string | null
    {
        return this._focusedNodeId;
    }

    /**
     * mode を none にし、ルートから depth 用クラスを外す
     */
    public reset(): void
    {
        this.setMode('none');
        this._focusedNodeId = null;
    }
}
