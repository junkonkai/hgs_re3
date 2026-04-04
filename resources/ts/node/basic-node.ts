import { NodeBase } from "./node-base";
import { AppearStatus } from "../enum/appear-status";
import { Util } from "../common/util";
import { TreeNodeInterface } from "./interface/tree-node-interface";
import { NodeContentBehind } from "./parts/node-content-behind";
import { NodeHead } from "./parts/node-head";
import { NodeHeadType } from "../common/type";
import { NodeHeadClickable } from "./parts/node-head-clickable";
import { CurveRenderer } from "./parts/renderers/curve-renderer";
import { CanvasCurveRenderer } from "./parts/renderers/canvas-curve-renderer";
import { SvgCurveRenderer } from "./parts/renderers/svg-curve-renderer";
import { Config } from "../common/config";
import { Point } from "../common/point";
import { ClickableNodeInterface } from "./interface/clickable-node-interface";
import { HgnTree } from "../hgn-tree";
import { CurrentNode } from "./current-node";
import { DepthEffectController } from "../depth/depth-effect-controller";

export class BasicNode extends NodeBase
{
    public isHomewardDisappear: boolean = false;
    protected _animationStartTime: number = 0;
    protected _updateGradientEndAlphaFunc: (() => void) | null = null;
    protected _parentNode: TreeNodeInterface;
    protected _nodeContentBehind: NodeContentBehind | null;
    protected _curveRenderer: CurveRenderer;
    protected _isFast: boolean = false;
    protected _doNotAppearBehind: boolean = false;
    protected _onDisappearOnlyComplete: (() => void) | null = null;

    public get parentNode(): TreeNodeInterface
    {
        return this._parentNode;
    }

    public get curveRenderer(): CurveRenderer
    {
        return this._curveRenderer;
    }

    public get behindContent(): NodeContentBehind | null
    {
        return this._nodeContentBehind;
    }

    /**
     * Phase3: 自身または behind に進行中アニメーションがあるか。
     */
    public hasActiveAnimation(): boolean
    {
        if (this._appearAnimationFunc !== null) {
            return true;
        }
        if (this._updateGradientEndAlphaFunc !== null) {
            return true;
        }
        if (this._nodeContentBehind && AppearStatus.isTransitioning(this._nodeContentBehind.appearStatus)) {
            return true;
        }
        return false;
    }

    /**
     * コンストラクタ
     */
    public constructor(nodeElement: HTMLElement, parentNode: TreeNodeInterface)
    {
        super(nodeElement);

        this._parentNode = parentNode;

        this._curveRenderer = Config.getInstance().USE_SVG_CURVE
            ? new SvgCurveRenderer(this)
            : new CanvasCurveRenderer(this);
        this._appearAnimationFunc = null;
        this._updateGradientEndAlphaFunc = null;

        this._nodeContentBehind = null;
        if (this._behindContentElement) {
            this._nodeContentBehind = new NodeContentBehind(this._behindContentElement as HTMLElement);
            this._nodeContentBehind.loadNodes();
        }

        // Phase1: node-head / node-content の <a href> を同一の遷移経路に乗せる
        this.getNavigableAnchors().forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                this.clickLink(anchor, e);
            });
        });

        const forms = Array.from(this._nodeElement.querySelectorAll(':scope > .node-content.basic form')) as HTMLFormElement[];
        forms.forEach(form => {
            // コンポーネント側で処理するやつは無視
            if (form.dataset.componentUse === '1') {
                return;
            }

            form.addEventListener('submit', (e) => {
                this.submitForm(form, e);
                return false;
            });
        });
    }

    /**
     * ノードのヘッダを読み込む
     * 
     * @returns 
     */
    protected loadHead(): NodeHeadType
    {
        // 自身の継承先がClickableNodeInterfaceを実装しているかをチェック
        const nodeHead = this._nodeElement.querySelector(':scope > .node-head') as HTMLElement;
        if (Util.isClickableNode(this)) {
            return new NodeHeadClickable(nodeHead, this as ClickableNodeInterface);
        }
        return new NodeHead(nodeHead);
    }

    /**
     * リサイズ時の処理
     */
    public resize(): void
    {
        this._curveRenderer.resize();
        this._nodeContentBehind?.resize();
        super.resize();
        this.setDraw();
    }

    /**
     * アニメーションの更新処理
     */
    public update(): void
    {
        super.update();

        this._nodeContentBehind?.update();

        this._appearAnimationFunc?.();
        this._updateGradientEndAlphaFunc?.();
    }

    /**
     * 出現アニメーション開始
     */
    public appear(isFast: boolean = false, doNotAppearBehind: boolean = false): void
    {
        if (AppearStatus.isDisappeared(this._appearStatus)) {
            this.startAppear(isFast, doNotAppearBehind);
        }
    }

    protected startAppear(isFast: boolean = false, doNotAppearBehind: boolean = false): void
    {
        this._appearStatus = AppearStatus.APPEARING;
        this._appearAnimationFunc = this.appearAnimation;
        this._animationStartTime = HgnTree.getInstance().timestamp;
        this._curveRenderer.setProgress(0);
        this._curveRenderer.setGradient(1, 0);
        this._curveRenderer.show();

        // 曲線の path を最初のフレームで設定する（draw() は _isDraw が true のときのみ path を設定するため、
        // ここで設定しないとボールだけ動き、曲線は終盤まで描画されない）
        const connectionPoint = this._nodeHead.getConnectionPoint();
        const startPoint = new Point(
            Math.floor(this._parentNode.nodeHead.getNodePtWidth() / 2),
            0
        );
        this._curveRenderer.setPath(startPoint, connectionPoint);

        this._nodeElement.classList.add('node-waiting-curve');
        this._nodeHead.nodeElement.classList.add('head-waiting-curve');

        this.freePt.setPos(Math.floor(this._parentNode.nodeHead.getNodePtWidth() / 2), 0).setElementPos();
        this.freePt.show();
        this._isFast = isFast;
        this._doNotAppearBehind = doNotAppearBehind;
        this.setDraw();
    }

    /**
     * 出現アニメーション
     */
    public appearAnimation(): void
    {
        const duration = Config.getInstance().CURVE_ANIMATION_DURATION;
        const progress = Util.getAnimationProgress(this._animationStartTime, duration);
        this._curveRenderer.setProgress(progress);

        const connectionPoint = this._nodeHead.getConnectionPoint();
        const pos = Util.getQuadraticBezierPoint(
            Math.floor(this._parentNode.nodeHead.getNodePtWidth() / 2), 0,
            connectionPoint.x, connectionPoint.y,
            progress
        );

        this.freePt.moveOffset(pos.x-10, pos.y);
        if (this._curveRenderer.getProgress() === 1) {
            this._curveRenderer.setGradient(1, 1);
            this._nodeElement.classList.remove('node-waiting-curve');
            this._nodeHead.nodeElement.classList.remove('head-waiting-curve');
            this._nodeHead.appear();
            this.freePt.hide();
            this.freePt.setPos(connectionPoint.x, connectionPoint.y).setElementPos();
            this.appearContents();

            if (this._nodeContentBehind && !this._doNotAppearBehind) {
                this._nodeContentBehind.appear();
                this._appearAnimationFunc = this.appearBehindAnimation;
            } else {
                this._appearAnimationFunc = null;
            }
            this._appearStatus = AppearStatus.APPEARED;
        }
        
        this._isDraw = true;
    }

    protected appearBehindAnimation(): void
    {
        if (this._nodeContentBehind && AppearStatus.isAppeared(this._nodeContentBehind.appearStatus)) {
            this._appearAnimationFunc = null;
            this._appearStatus = AppearStatus.APPEARED;
        }

        this._isDraw = true;
    }

    /**
     * 消滅アニメーション開始
     */
    public disappear(isFast: boolean = false, doNotAppearBehind: boolean = false): void
    {
        if (AppearStatus.isDisappeared(this.appearStatus) || AppearStatus.isDisappearing(this.appearStatus)) {
            return;
        }

        if (this.isHomewardDisappear) {
            this.homewardDisappear();
        } else {
            this._isFast = isFast;
            this.disappearContents();
            this._animationStartTime = HgnTree.getInstance().timestamp;
            this._curveRenderer.setGradient(1, 0);
            this._curveRenderer.hide();
            this._appearStatus = AppearStatus.DISAPPEARING;
            this._updateGradientEndAlphaFunc = null;

            if (!doNotAppearBehind) {
                this._nodeContentBehind?.disappear();
            }

            this._isDraw = true;

            this._appearAnimationFunc = this.disappearAnimation;
        }
    }

    /**
     * 消滾アニメーション
     */
    protected disappearAnimation(): void
    {
        if (this.curveDisappearAnimation()) {
            this._appearAnimationFunc = null;
            this._appearStatus = AppearStatus.DISAPPEARED;
            this._curveRenderer.hide();
            this._nodeHead.disappearFadeOut();
            if (this._onDisappearOnlyComplete) {
                const cb = this._onDisappearOnlyComplete;
                this._onDisappearOnlyComplete = null;
                cb();
            }
        }
        
        this._isDraw = true;
    }

    /**
     * このノードのみ消滅（internal-node 更新用）。完了時に onComplete を呼ぶ。
     */
    public disappearOnlyThisNode(onComplete?: () => void): void
    {
        if (AppearStatus.isDisappeared(this.appearStatus) || AppearStatus.isDisappearing(this.appearStatus)) {
            onComplete?.();
            return;
        }
        this._onDisappearOnlyComplete = onComplete ?? null;
        this._isFast = false;
        this.disappearContents();
        this._animationStartTime = HgnTree.getInstance().timestamp;
        this._curveRenderer.setGradient(1, 0);
        this._appearStatus = AppearStatus.DISAPPEARING;
        this._updateGradientEndAlphaFunc = null;
        this._nodeContentBehind?.disappear();
        this._isDraw = true;
        this._appearAnimationFunc = this.disappearAnimation;
    }

    protected curveDisappearAnimation(): boolean
    {
        this._curveRenderer.setProgress(0);
        const p = this._curveRenderer.getProgress();
        this._curveRenderer.setGradient(p * 0.7, p * 0.7);
        if (p === 0) {
            this._curveRenderer.setGradient(0, 0);
            return true;
        }

        return false;
    }

    /**
     * ホバー開始時のグラデーションα値を更新
     */
    protected updateGradientEndAlphaOnHover(): void
    {
        const endAlpha = Util.getAnimationValue(0.3, 1.0, this._animationStartTime, 300);
        this._curveRenderer.setGradient(1, endAlpha);
        if (endAlpha === 1) {
            this._updateGradientEndAlphaFunc = null;
        }
        this.setDraw();
    }

    /**
     * ホバー終了時のグラデーションα値を更新
     */
    protected updateGradientEndAlphaOnUnhover(): void
    {
        let endAlpha = Util.getAnimationValue(1.0, 0.3, this._animationStartTime, 300);
        if (endAlpha <= 0.3) {
            endAlpha = 0.3;
            this._updateGradientEndAlphaFunc = null;
        }
        this._curveRenderer.setGradient(1, endAlpha);
        this.setDraw();
    }

    /**
     * 描画処理
     */
    public draw(): void
    {
        if (!this._isDraw) {
            return;
        }

        this._curveRenderer.clear();

        const connectionPoint = this._nodeHead.getConnectionPoint();

        const startPoint = new Point(
            Math.floor(this._parentNode.nodeHead.getNodePtWidth() / 2),
            0
        );

        this._curveRenderer.setPath(startPoint, connectionPoint);
        this._curveRenderer.setProgress(this._curveRenderer.getProgress());

        // 背景ノードの描画
        this._nodeContentBehind?.draw(this._curveRenderer, connectionPoint);
    
        this._isDraw = false;
    }

    /**
     * Phase5: persistent モード用。自ノード要素に depth を適用する。
     */
    public applyDepth(depth: number): void
    {
        DepthEffectController.getInstance().applyDepth(this._nodeElement, depth);
    }

    /**
     * Phase5: 自ノードから depth スタイルを削除する。
     */
    public clearDepth(): void
    {
        DepthEffectController.getInstance().clearDepth(this._nodeElement);
    }

    /**
     * Phase1: 遷移に使うアンカーを収集（node-head と node-content の <a href>）。
     * data-hgn-scope 未指定時は rel から scope を補うため、すべてのアンカーを対象にする。
     */
    public getNavigableAnchors(): HTMLAnchorElement[]
    {
        const head = this._nodeElement.querySelectorAll(':scope > .node-head a[href]');
        const content = this._nodeElement.querySelectorAll(':scope > .node-content a[href]');
        const ownContentAnchors = Array.from(content).filter(anchor => {
            const ownerNode = anchor.closest('section.node');
            return ownerNode === this._nodeElement;
        });
        return [...Array.from(head), ...ownContentAnchors] as HTMLAnchorElement[];
    }

    /**
     * クリック時の処理（Phase1: NavigationController に委譲、未設定時は従来の rel ベース）
     */
    public clickLink(anchor: HTMLAnchorElement, e: MouseEvent): void
    {
        const nodeContentTree = this.parentNode.nodeContentTree;
        if (!AppearStatus.isAppeared(nodeContentTree.appearStatus)) {
            return;
        }

        const nav = HgnTree.getInstance().navigationController;
        if (nav) {
            // external scope（target="_blank" / data-hgn-scope="external" / rel="external"）はブラウザに任せる
            const isExternal = anchor.target === '_blank'
                || anchor.dataset.hgnScope === 'external'
                || (anchor.getAttribute('rel') ?? '').split(/\s+/).includes('external');
            if (isExternal) {
                return;
            }
            e.preventDefault();
            nav.navigateFromAnchor(anchor, this);
            return;
        }

        // 後方互換: NavigationController 未設定時
        if (anchor.getAttribute('rel') === 'external') {
            location.href = anchor.href;
            return;
        }
        e.preventDefault();
        const currentNode = HgnTree.getInstance().currentNode as CurrentNode;
        const rel = anchor.getAttribute('rel') ?? '';
        if (rel === 'internal-node') {
            currentNode.updateSingleNode(anchor.href, this);
            return;
        }
        currentNode.moveNode(anchor.href, false);
        this.disappearStart();
    }

    /**
     * 消滾アニメーション開始
     */
    public disappearStart(): void
    {
        const headPos = this.nodeHead.getConnectionPoint();
        HgnTree.getInstance().calculateDisappearSpeedRate(headPos.y + window.scrollY);

        this.isHomewardDisappear = true;
        this.parentNode.prepareDisappear(this);
        this.disappearContents();
    }

    /**
     * フォーム送信時の処理
     * 
     * @param form 送信したフォーム
     * @param e 送信イベント
     */
    public submitForm(form: HTMLFormElement, e: SubmitEvent): void
    {
        e.preventDefault();

        const nodeContentTree = this.parentNode.nodeContentTree;
        if (!AppearStatus.isAppeared(nodeContentTree.appearStatus)) {
            return;
        }

        const currentNode = HgnTree.getInstance().currentNode as CurrentNode;
        const isChildOnly = form.dataset.childOnly === '1';

        if (form.method.toUpperCase() !== 'POST') {
            const params = new URLSearchParams(new FormData(form) as any);
            currentNode.moveNode(form.action + '?' + params.toString(), false, isChildOnly);
        } else {
            const isNoPushState = form.dataset.noPushState === '1';
            const formData = new FormData(form);
            currentNode.postData(form.action, formData, isChildOnly, isNoPushState);
        }

        this.disappearStart();
    }

    /**
     * 消滾アニメーション
     */
    public selectedDisappearAnimation(): void
    {
        const connectionPoint = this.nodeHead.getConnectionPoint();

        const freePt = this.freePt;

        const duration = Config.getInstance().CURVE_ANIMATION_DURATION;
        const progress = 1 - Util.getAnimationProgress(this._animationStartTime, duration);
        this._curveRenderer.setProgress(progress);

        if (this._curveRenderer.getProgress() === 0) {
            this._curveRenderer.setGradient(1, 0);
            this._appearAnimationFunc = this.selectedDisappearAnimation2;

            this._animationStartTime = HgnTree.getInstance().timestamp;
        } else {
            const x = this.nodeHead.nodePoint.htmlElement.offsetWidth / 2;
            this._curveRenderer.setPath(new Point(x, 0), connectionPoint);
            this._curveRenderer.setProgress(progress);

            const pos = Util.getQuadraticBezierPoint(
                0, 0,
                connectionPoint.x, connectionPoint.y,
                progress
            );

            freePt.moveOffset(pos.x, pos.y);
        }
        
        this._isDraw = true;
    }

    /**
     * 消滾アニメーション2
     */
    public selectedDisappearAnimation2(): void
    {
        this.isHomewardDisappear = false;
        this._appearAnimationFunc = null;
        this._appearStatus = AppearStatus.DISAPPEARED;

        this.freePt.hide();
        this._parentNode.homewardDisappear();
    }

    /**
     * ホームワード消滾処理
     */
    public homewardDisappear(): void
    {
        if (AppearStatus.isDisappeared(this.appearStatus) || AppearStatus.isDisappearing(this.appearStatus)) {
            return;
        }
        this.disappearContents();

        // TreeNodeの場合は_nodeContentTreeも消滾させる
        const freePt = this.freePt;
        this._updateGradientEndAlphaFunc = null;

        const nodeHeadPointWidth = this.nodeHead.nodePoint.htmlElement.offsetWidth / 2;
        freePt.setPos(nodeHeadPointWidth, 0).setElementPos();
        freePt.show();
        this.nodeHead.nodePoint.hidden();
        
        this._nodeContentBehind?.disappear();

        this._animationStartTime = HgnTree.getInstance().timestamp;
        this._appearStatus = AppearStatus.DISAPPEARING;
        this._curveRenderer.setGradient(1, 0);
        this.nodeHead.disappear();
        this._isDraw = true;

        this._appearAnimationFunc = this.selectedDisappearAnimation;
    }
} 