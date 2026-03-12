import { NodePoint } from "./node-point";
import { Point } from "../../common/point";
import { AppearStatus } from "../../enum/appear-status";

export class NodeHead
{
    protected _nodePoint: NodePoint;
    protected _nodeElement: HTMLElement;
    protected _titleElement: HTMLElement;
    protected _appearStatus: AppearStatus;
    protected _currentAbortController: AbortController | null = null;

    public get nodePoint(): NodePoint
    {
        return this._nodePoint;
    }

    public get titleElement(): HTMLElement
    {
        return this._titleElement;
    }

    public get appearStatus(): AppearStatus
    {
        return this._appearStatus;
    }

    public set title(title: string)
    {
        this._titleElement.innerHTML = title;
    }

    public get nodeElement(): HTMLElement
    {
        return this._nodeElement;
    }

    /**
     * コンストラクタ
     */
    public constructor(nodeElement: HTMLElement)
    {
        this._nodeElement = nodeElement;
        this._nodePoint = new NodePoint(nodeElement.querySelector(':scope > .node-pt') as HTMLSpanElement);
        this._titleElement = this._nodeElement.querySelector(':scope > .node-head-text') as HTMLElement;
        this._appearStatus = AppearStatus.DISAPPEARED;
    }

    /**
     * 接続点を取得する
     * 
     * @returns 接続点
     */
    public getConnectionPoint(): Point
    {
        return this._nodePoint.getCenterPosition();
    }

    public getNodePtWidth(): number
    {
        return this._nodePoint.htmlElement.offsetWidth;
    }

    public getNodeHeight(): number
    {
        return this._nodeElement.offsetHeight;
    }

    /**
     * HTML上の絶対座標で接続点を取得する
     * 
     * @returns 絶対座標の接続点
     */
    public getAbsoluteConnectionPoint(): {x: number, y: number}
    {
        let position = this._nodePoint.getAbsoluteCenterPosition();
        position.x -= 1;

        return position;
    }

    public appear(): Promise<void>
    {
        // 既存のアニメーションをキャンセル
        this.cancelCurrentAnimation();

        this._nodeElement.classList.remove('head-waiting-curve');
        this._nodePoint.appear();
        this._titleElement.classList.remove('head-fade-out');
        this._titleElement.classList.remove('head-reveal-out');
        this._titleElement.classList.add('head-reveal-in');
        
        this._currentAbortController = new AbortController();
        return this.waitForAnimationEnd('head-reveal-in-mask', this._currentAbortController.signal).then(() => {
            this._appearStatus = AppearStatus.APPEARED;
            this._currentAbortController = null;
        }).catch(() => {
            // キャンセルされた場合は状態を更新しない
            this._currentAbortController = null;
        });
    }

    public disappear(): Promise<void>
    {
        if (!AppearStatus.isAppeared(this._appearStatus)) {
            return Promise.resolve();
        }

        // 既存のアニメーションをキャンセル
        this.cancelCurrentAnimation();
        
        this._titleElement.classList.remove('head-reveal-in');
        this._titleElement.classList.add('head-reveal-out');
        //this._nodePoint.hidden();
        
        this._currentAbortController = new AbortController();
        return this.waitForAnimationEnd('head-reveal-out-mask', this._currentAbortController.signal).then(() => {
            this._appearStatus = AppearStatus.DISAPPEARED;
            this._currentAbortController = null;
        }).catch(() => {
            // キャンセルされた場合は状態を更新しない
            this._currentAbortController = null;
        });
    }

    public disappearFadeOut(): void
    {
        this._nodePoint.disappear();
        this._titleElement.classList.add('head-fade-out');
    }

    /**
     * アニメーション完了を待つ
     * 
     * @param animationName アニメーション名
     * @param abortSignal キャンセル用のシグナル
     * @returns Promise<void>
     */
    private waitForAnimationEnd(animationName: string, abortSignal: AbortSignal): Promise<void>
    {
        return new Promise((resolve, reject) => {
            const handleAnimationEnd = (event: AnimationEvent) => {
                if (event.animationName === animationName) {
                    this._titleElement.removeEventListener('animationend', handleAnimationEnd);
                    resolve();
                }
            };
            
            const handleAbort = () => {
                this._titleElement.removeEventListener('animationend', handleAnimationEnd);
                reject(new Error('Animation cancelled'));
            };
            
            this._titleElement.addEventListener('animationend', handleAnimationEnd);
            abortSignal.addEventListener('abort', handleAbort);
        });
    }

    /**
     * 現在実行中のアニメーションをキャンセルする
     */
    public cancelCurrentAnimation(): void
    {
        if (this._currentAbortController) {
            this._currentAbortController.abort();
            this._currentAbortController = null;
        }
        
        // アニメーションクラスを削除してアニメーションを停止
        this._titleElement.classList.remove('head-reveal-in', 'head-reveal-out');
        
        // アニメーションを即座に停止するためのCSSプロパティを設定
        this._titleElement.style.animation = 'none';
        // 次のフレームでリセット
        requestAnimationFrame(() => {
            this._titleElement.style.animation = '';
        });
    }

    public setTitle(title: string): void
    {
        this._titleElement.innerHTML = title;
    }
}
