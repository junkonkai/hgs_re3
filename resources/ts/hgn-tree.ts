import { CurrentNode } from "./node/current-node";
import { AppearStatus } from "./enum/appear-status";
import Cookies from "js-cookie";
import { NavigationController } from "./navigation/navigation-controller";
import { NavigationFetcher } from "./navigation/navigation-fetcher";
import { NavigationStateStore } from "./navigation/navigation-state-store";
import { HistoryCoordinator } from "./navigation/history-coordinator";
import type { NavigationHistoryState } from "./navigation/types";
import { AnimationScheduler } from "./animation/animation-scheduler";
import type { Animatable } from "./animation/animatable";
import { DepthSceneController } from "./depth/depth-scene-controller";

/**
 * アプリ全体のルートクラス。
 * Phase3: 常時 rAF を廃止し、AnimationScheduler でアニメーション中だけループする。
 * Phase6: 正式名称を HgnTree に統一。
 */
export class HgnTree
{
    private static _instance: HgnTree;
    private _timestamp: number = 0;
    private _mainElement: HTMLElement;

    private _currentNode: CurrentNode;
    private _depthSceneController: DepthSceneController;
    private _navigationController: NavigationController;
    private _historyCoordinator: HistoryCoordinator;
    private _disappearSpeedRate: number = 1;
    private _animationScheduler: AnimationScheduler;
    private _sceneAnimatable: Animatable;

    public isForceResize: boolean = false;

    /**
     * インスタンスを返す
     */
    public static getInstance(): HgnTree
    {
        if (!HgnTree._instance) {
            HgnTree._instance = new HgnTree();
        }
        return HgnTree._instance;
    }

    /**
     * 現在のタイムスタンプを取得
     */
    public get timestamp(): number
    {
        return this._timestamp;
    }

    /**
     * main要素を取得
     */
    public get mainElement(): HTMLElement
    {
        return this._mainElement;
    }

    /**
     * カレントノードを取得
     */
    public get currentNode(): CurrentNode
    {
        return this._currentNode;
    }

    /**
     * Phase1: ナビゲーションコントローラーを取得
     */
    public get navigationController(): NavigationController
    {
        return this._navigationController;
    }

    /**
     * Phase5: Z軸演出のシーンコントローラーを取得
     */
    public get depthSceneController(): DepthSceneController
    {
        return this._depthSceneController;
    }

    public get disappearSpeedRate(): number
    {
        return this._disappearSpeedRate;
    }

    /**
     * Phase3: アニメーションスケジューラーを取得
     */
    public get animationScheduler(): AnimationScheduler
    {
        return this._animationScheduler;
    }

    /**
     * Phase3: アニメーション開始時に登録＋requestTick する。静止時ループ停止後に再開する用。
     */
    public requestAnimationFrameIfNeeded(): void
    {
        this._animationScheduler.register(this._sceneAnimatable);
        this._animationScheduler.requestTick();
    }

    /**
     * コンストラクタ
     */
    private constructor()
    {
        this._mainElement = document.querySelector('main') as HTMLElement;
        this._currentNode = new CurrentNode(this._mainElement.querySelector('#current-node') as HTMLElement);
        this._depthSceneController = new DepthSceneController(this._currentNode.nodeElement);
        const stateStore = new NavigationStateStore();
        const fetcher = new NavigationFetcher();
        this._historyCoordinator = new HistoryCoordinator();
        this._navigationController = new NavigationController(this._currentNode, fetcher, stateStore, this._historyCoordinator, this._depthSceneController);
        this._animationScheduler = new AnimationScheduler();
        const self = this;
        this._sceneAnimatable = {
            update(timestamp: number): boolean
            {
                self.onAnimationFrame(timestamp);
                return self._currentNode.hasActiveAnimation();
            },
        };
    }

    /**
     * 開始処理
     */
    public start(): void
    {
        // リサイズイベントの登録
        const target = document.body; // 監視対象
        let lastWidth = target.offsetWidth;
        let lastHeight = target.offsetHeight;
        
        const ro = new ResizeObserver(entries => {
          for (const entry of entries) {
            const { width, height } = entry.contentRect;

            if (height !== lastHeight) {
                this.isForceResize = true;
                this.requestAnimationFrameIfNeeded();
            }

            lastWidth = width;
            lastHeight = height;
          }
        });
        
        ro.observe(target);
        
        // ページ遷移前のイベント登録
        window.addEventListener('beforeunload', () => {
            sessionStorage.setItem('isPageTransition', 'true');
        });

        // ページ表示イベントの登録（キャッシュからの復元時）
        window.addEventListener('pageshow', (event) => {
            const isPageTransition = sessionStorage.getItem('isPageTransition') === 'true';
            if (event.persisted && !isPageTransition) {
                this.resize();
                this.draw();
            }
            sessionStorage.removeItem('isPageTransition');
        });

        // popstateイベントの登録
        window.addEventListener('popstate', (event) => { this.popState(event); });

        this._currentNode.start();
        this.resize();
        this._currentNode.appear();

        // Phase5: Z軸演出を transition モードで有効化
        this._depthSceneController.setMode('transition');

        // Phase2: 初期状態の履歴を NavigationHistoryState 形式で設定
        const initialState: NavigationHistoryState = {
            url: window.location.href,
            scope: 'full',
            urlPolicy: 'replace',
        };
        history.replaceState(initialState, '', window.location.href);

        this._animationScheduler.register(this._sceneAnimatable);
        this._animationScheduler.requestTick();
    }

    /**
     * リサイズ時の処理
     */
    private resize(): void
    {
        this._currentNode.resize();
    }

    /**
     * Phase3: Scheduler から必要時だけ呼ばれる 1 フレーム処理。
     */
    private onAnimationFrame(timestamp: number): void
    {
        this._timestamp = timestamp;

        if (this.isForceResize) {
            this.resize();
            this.isForceResize = false;
        }

        this._currentNode.update();
        this.draw();
    }

    /**
     * 描画処理
     */
    public draw(): void
    {
        this._currentNode.draw();
    }

    /**
     * popstateイベントの処理（Phase2: HistoryCoordinator で scope / sourceNodeId を復元）
     */
    private popState(event?: PopStateEvent): void
    {
        const state = event?.state;
        if (!state?.url) {
            return;
        }

        // Phase2: 新形式 (scope, sourceNodeId) なら HistoryCoordinator で request 復元
        const request = this.isNewHistoryState(state)
            ? this._historyCoordinator.createRequestFromPopState(state as NavigationHistoryState)
            : {
                url: state.url,
                scope: (state as { isChildOnly?: boolean }).isChildOnly === true ? 'children' as const : 'full' as const,
                urlPolicy: 'popstate' as const,
            };
        if (!request) {
            return;
        }

        this._navigationController.navigate(request);

        if (!AppearStatus.isAppeared(this._currentNode.appearStatus)) {
            location.href = state.url;
        }
    }

    private isNewHistoryState(state: unknown): state is NavigationHistoryState
    {
        return typeof state === 'object' && state !== null && 'scope' in state && typeof (state as NavigationHistoryState).scope === 'string';
    }

    public calculateDisappearSpeedRate(disappearStartPos: number): void
    {
        if (disappearStartPos <= 700) {
            this._disappearSpeedRate = 1;
        } else {
            // 100px毎に0.2ずつ増加
            this._disappearSpeedRate = 1 + ((disappearStartPos - 700) / 100 * 0.2);
        }
    }

    public getOver18(): boolean
    {
        const isOver18 = Cookies.get('over_18');
        return isOver18 === '1';
    }

    public setOver18(value: boolean)
    {
        Cookies.set('over_18', value ? '1' : '0');
    }
}

/** Phase6: 互換用。最終的には削除する。 */
export { HgnTree as HorrorGameNetwork }; 
