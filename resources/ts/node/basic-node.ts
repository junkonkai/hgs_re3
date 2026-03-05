import { NodeBase } from "./node-base";
import { AppearStatus } from "../enum/appear-status";
import { Util } from "../common/util";
import { TreeNodeInterface } from "./interface/tree-node-interface";
import { NodeContentBehind } from "./parts/node-content-behind";
import { NodeHead } from "./parts/node-head";
import { NodeHeadType } from "../common/type";
import { NodeHeadClickable } from "./parts/node-head-clickable";
import { CurveCanvas } from "./parts/curve-canvas";
import { Point } from "../common/point";
import { ClickableNodeInterface } from "./interface/clickable-node-interface";
import { HorrorGameNetwork } from "../horror-game-network";
import { CurrentNode } from "./current-node";

export class BasicNode extends NodeBase
{
    public isHomewardDisappear: boolean = false;
    protected _animationStartTime: number = 0;
    protected _updateGradientEndAlphaFunc: (() => void) | null = null;
    protected _parentNode: TreeNodeInterface;
    protected _nodeContentBehind: NodeContentBehind | null;
    protected _curveCanvas: CurveCanvas;
    protected _isFast: boolean = false;
    protected _doNotAppearBehind: boolean = false;
    protected _onDisappearOnlyComplete: (() => void) | null = null;

    public get parentNode(): TreeNodeInterface
    {
        return this._parentNode;
    }

    public get curveCanvas(): CurveCanvas
    {
        return this._curveCanvas;
    }

    public get behindContent(): NodeContentBehind | null
    {
        return this._nodeContentBehind;
    }

    /**
     * コンストラクタ
     */
    public constructor(nodeElement: HTMLElement, parentNode: TreeNodeInterface)
    {
        super(nodeElement);

        this._parentNode = parentNode;

        this._curveCanvas = new CurveCanvas(this);
        this._appearAnimationFunc = null;
        this._updateGradientEndAlphaFunc = null;

        this._nodeContentBehind = null;
        if (this._behindContentElement) {
            this._nodeContentBehind = new NodeContentBehind(this._behindContentElement as HTMLElement);
            this._nodeContentBehind.loadNodes();
        }

        // .node-content a かつ、relがinternalまたはinternal-nodeであるもの
        const internalAnchors = Array.from(this._nodeElement.querySelectorAll(':scope > .node-content.basic a[rel="internal"]')) as HTMLAnchorElement[];
        internalAnchors.forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                this.clickLink(anchor, e);
            });
        });
        const internalNodeAnchors = Array.from(this._nodeElement.querySelectorAll(':scope > .node-content.basic a[rel="internal-node"]')) as HTMLAnchorElement[];
        internalNodeAnchors.forEach(anchor => {
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
        this._animationStartTime = (window as any).hgn.timestamp;
        this._curveCanvas.appearProgress = 0;
        this._curveCanvas.gradientStartAlpha = 1;
        this._curveCanvas.gradientEndAlpha = 0;

        this.freePt.setPos(Math.floor(this._parentNode.nodeHead.getNodePtWidth() / 2), 0).setElementPos();
        this.freePt.show();
        this._isFast = isFast;
        this._doNotAppearBehind = doNotAppearBehind;
    }

    /**
     * 出現アニメーション
     */
    public appearAnimation(): void
    {
        this._curveCanvas.appearProgress = Util.getAnimationProgress(this._animationStartTime, this._isFast ? 50 : 100);
        
        const connectionPoint = this._nodeHead.getConnectionPoint();
        const pos = Util.getQuadraticBezierPoint(
            Math.floor(this._parentNode.nodeHead.getNodePtWidth() / 2), 0,
            connectionPoint.x, connectionPoint.y,
            this._curveCanvas.appearProgress
        );

        this.freePt.moveOffset(pos.x-10, pos.y);
        if (this._curveCanvas.appearProgress === 1) {
            this._curveCanvas.gradientEndAlpha = 1;
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
            this._animationStartTime = (window as any).hgn.timestamp;
            this._curveCanvas.gradientEndAlpha = 0;
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
        this._animationStartTime = (window as any).hgn.timestamp;
        this._curveCanvas.gradientEndAlpha = 0;
        this._appearStatus = AppearStatus.DISAPPEARING;
        this._updateGradientEndAlphaFunc = null;
        this._nodeContentBehind?.disappear();
        this._isDraw = true;
        this._appearAnimationFunc = this.disappearAnimation;
    }

    protected curveDisappearAnimation(): boolean
    {
        this._curveCanvas.appearProgress = 0;//1 - Util.getAnimationProgress(this._animationStartTime, 10);
        this._curveCanvas.gradientEndAlpha = this._curveCanvas.appearProgress * 0.7;
        if (this._curveCanvas.appearProgress === 0) {
            this._curveCanvas.gradientEndAlpha = 0;
            this._curveCanvas.gradientStartAlpha = 0;
            return true;
        }

        return false;
    }

    /**
     * ホバー開始時のグラデーションα値を更新
     */
    protected updateGradientEndAlphaOnHover(): void
    {
        this._curveCanvas.gradientEndAlpha = Util.getAnimationValue(0.3, 1.0, this._animationStartTime, 300);
        if (this._curveCanvas.gradientEndAlpha === 1) {
            this._updateGradientEndAlphaFunc = null;
        }
        this.setDraw();
    }

    /**
     * ホバー終了時のグラデーションα値を更新
     */
    protected updateGradientEndAlphaOnUnhover(): void
    {
        this._curveCanvas.gradientEndAlpha = Util.getAnimationValue(1.0, 0.3, this._animationStartTime, 300);
        if (this._curveCanvas.gradientEndAlpha <= 0.3) {
            this._curveCanvas.gradientEndAlpha = 0.3;
            this._updateGradientEndAlphaFunc = null;
        }
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

        this._curveCanvas.clearCanvas();

        const connectionPoint = this._nodeHead.getConnectionPoint();

        const startPoint = new Point(
            Math.floor(this._parentNode.nodeHead.getNodePtWidth() / 2),
            0
        );

        this._curveCanvas.drawCurvedLine(startPoint, connectionPoint);

        // 背景ノードの描画
        this._nodeContentBehind?.draw(this._curveCanvas, connectionPoint);
    
        this._isDraw = false;
    }

    /**
     * クリック時の処理
     * @param anchor クリックしたアンカー
     * @param e クリックイベント
     */
    public clickLink(anchor: HTMLAnchorElement, e: MouseEvent): void
    {
        // 外部リンクの場合は処理しない
        if (anchor.getAttribute('rel') === 'external') {
            location.href = anchor.href;
            return;
        }

        const nodeContentTree = this.parentNode.nodeContentTree;
        if (!AppearStatus.isAppeared(nodeContentTree.appearStatus)) {
            return;
        }

        e.preventDefault();

        const rel = anchor.getAttribute('rel') ?? '';
        const hgn = (window as any).hgn as HorrorGameNetwork;
        const currentNode = hgn.currentNode as CurrentNode;

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
        const hgn = (window as any).hgn as HorrorGameNetwork;
        hgn.calculateDisappearSpeedRate(headPos.y + window.scrollY);

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

        const hgn = (window as any).hgn as HorrorGameNetwork;
        const currentNode = hgn.currentNode as CurrentNode;
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

        const hgn = (window as any).hgn as HorrorGameNetwork;
        const freePt = this.freePt;

        this._curveCanvas.appearProgress = 1 - Util.getAnimationProgress(this._animationStartTime, 100);

        if (this._curveCanvas.appearProgress === 0) {
            this._curveCanvas.gradientEndAlpha = 0;
            this._appearAnimationFunc = this.selectedDisappearAnimation2;

            this._animationStartTime = hgn.timestamp;
        } else {
            const x = this.nodeHead.nodePoint.htmlElement.offsetWidth / 2;
            this._curveCanvas.drawCurvedLine(new Point(x, 0), connectionPoint);

            const pos = Util.getQuadraticBezierPoint(
                0, 0,
                connectionPoint.x, connectionPoint.y,
                this._curveCanvas.appearProgress
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
        const hgn = (window as any).hgn as HorrorGameNetwork;
        const freePt = this.freePt;
        this._updateGradientEndAlphaFunc = null;

        const nodeHeadPointWidth = this.nodeHead.nodePoint.htmlElement.offsetWidth / 2;
        freePt.setPos(nodeHeadPointWidth, 0).setElementPos();
        freePt.show();
        this.nodeHead.nodePoint.hidden();
        
        this._nodeContentBehind?.disappear();

        this._animationStartTime = hgn.timestamp;
        this._appearStatus = AppearStatus.DISAPPEARING;
        this._curveCanvas.gradientEndAlpha = 0;
        this.nodeHead.disappear();
        this._isDraw = true;

        this._appearAnimationFunc = this.selectedDisappearAnimation;
    }
} 