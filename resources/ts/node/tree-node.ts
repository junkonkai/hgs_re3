import { BasicNode } from "./basic-node";
import { AppearStatus } from "../enum/appear-status";
import { NodeContentTree } from "./parts/node-content-tree";
import { FreePoint } from "./parts/free-point";
import { TreeNodeInterface } from "./interface/tree-node-interface";
import { NodeType } from "../common/type";
import { Util } from "../common/util";
import { Point } from "../common/point";

export class TreeNode extends BasicNode implements TreeNodeInterface
{
    protected _nodeContentTree: NodeContentTree;
    protected _homewardNode: NodeType | null;

    /**
     * ノードコンテンツツリーを取得
     */
    public get nodeContentTree(): NodeContentTree
    {
        return this._nodeContentTree;
    }

    /**
     * 帰路ノードを取得
     */
    public get homewardNode(): NodeType | null
    {
        return this._homewardNode;
    }

    /**
     * コンストラクタ
     * @param nodeElement ノードの要素
     */
    public constructor(nodeElement: HTMLElement, parentNode: TreeNodeInterface)
    {
        super(nodeElement, parentNode);

        this._nodeContentTree = new NodeContentTree(this._treeContentElement as HTMLElement, this);
        this._nodeContentTree.loadNodes(this);
        this._homewardNode = null;
    }

    public resize(): void
    {
        super.resize();
        this._nodeContentTree.resize();
    }

    /**
     * アニメーションの更新処理
     */
    public update(): void
    {
        super.update();

        this._nodeContentTree.update();
    }

    public appear(isFast: boolean = false): void
    {
        if (AppearStatus.isDisappeared(this._appearStatus)) {
            super.appear(isFast);
            this._appearAnimationFunc = this.appearAnimation;
        }
    }

    public appearAnimation(): void
    {
        super.appearAnimation();
        
        if (this._curveCanvas.appearProgress === 1) {
            this._nodeContentTree.appear(this._isFast);
            this._curveCanvas.gradientEndAlpha = 1;
            this._animationStartTime = (window as any).hgn.timestamp;
            this._appearAnimationFunc = this.appearAnimation2;
            this.freePt.show();
        }
    }

    public appearAnimation2(): void
    {
        if (AppearStatus.isAppeared(this._nodeContentTree.appearStatus)) {
            this._appearAnimationFunc = null;
            this._appearStatus = AppearStatus.APPEARED;
        }
    }

    
    /**
     * サブノードの出現アニメーション
     */
    protected appearSubNodesAnimation(): void
    {
        
    }

    public prepareDisappear(homewardNode: NodeType): void
    {
        this._homewardNode = homewardNode;
        this._parentNode.prepareDisappear(this);
    }

    public disappear(isFast: boolean = false): void
    {
        if (this._homewardNode === null) {
            super.disappear(isFast);
        }

        this._appearStatus = AppearStatus.DISAPPEARING;
        this._nodeContentTree.homewardNode = this._homewardNode;
        this._nodeContentTree.disappear(isFast);

        this._nodeContentBehind?.disappear();
    }

    public homewardDisappear(): void
    {
        this._nodeContentTree.disappearConnectionLine();
        this._appearAnimationFunc = this.homewardDisappearAnimation;
        this._animationStartTime = (window as any).hgn.timestamp;
        this._nodeHead.disappear();
        this.disappearContents();
    }

    public homewardDisappearAnimation(): void
    {
        if (this._nodeContentTree.appearStatus === AppearStatus.DISAPPEARED) {
            this._animationStartTime = (window as any).hgn.timestamp;
            this._curveCanvas.appearProgress = 1;
            this._appearAnimationFunc = this.homewardDisappearAnimation2;

            const freePt = this.freePt;
    
            const x = this.nodeHead.nodePoint.htmlElement.offsetWidth / 2;
            freePt.setPos(x, 0).setElementPos();
            freePt.show();

            const connectionPoint = this._nodeHead.getConnectionPoint();
            const pos = Util.getQuadraticBezierPoint(
                0, 0,
                connectionPoint.x - x + 3, connectionPoint.y,
                this._curveCanvas.appearProgress
            );
    
            freePt.moveOffset(pos.x, pos.y);
            this._nodeHead.nodePoint.hidden();
        }
    }

    public homewardDisappearAnimation2(): void
    {
        const connectionPoint = this._nodeHead.getConnectionPoint();
        const freePt = this.freePt;

        this._curveCanvas.appearProgress = 1 - Util.getAnimationProgress(this._animationStartTime, 50);
        this._curveCanvas.gradientStartAlpha = this._curveCanvas.appearProgress;
        this._curveCanvas.gradientEndAlpha = this._curveCanvas.appearProgress / 3;
        if (this._curveCanvas.appearProgress === 0) {
            this._curveCanvas.gradientStartAlpha = 0;
            this._curveCanvas.gradientEndAlpha = 0;
            this._homewardNode = null;
            this._appearAnimationFunc = null;
            this._appearStatus = AppearStatus.DISAPPEARED;

            freePt.hide();
            this.parentNode.homewardDisappear();
        } else { 
            const x = this.nodeHead.nodePoint.htmlElement.offsetWidth / 2;
            this._curveCanvas.drawCurvedLine(new Point(x, 0), connectionPoint);

            const pos = Util.getQuadraticBezierPoint(
                0, 0,
                connectionPoint.x - 15, connectionPoint.y,
                this._curveCanvas.appearProgress
            );
    
            freePt.moveOffset(pos.x, pos.y);
        }
        
        this._isDraw = true;
    }

    public draw(): void
    {
        super.draw();
        this._nodeContentTree.draw();
    }

    public resizeConnectionLine(): void
    {
        this._nodeContentTree.resizeConnectionLine(this._nodeHead.getConnectionPoint());
        // 親にも伝播させる
        this._parentNode.resizeConnectionLine();
    }

    public getNodeById(id: string): NodeType | null
    {
        return this._nodeContentTree.getNodeById(id);
    }

    /**
     * このノードのみ消滅（internal-node 更新用）。子ツリーと自身のヘッダ・曲線を消し、完了時に onComplete を呼ぶ。
     */
    public disappearOnlyThisNode(onComplete?: () => void): void
    {
        if (AppearStatus.isDisappeared(this._appearStatus) || AppearStatus.isDisappearing(this._appearStatus)) {
            onComplete?.();
            return;
        }
        let treeDone = false;
        let selfDone = false;
        const check = (): void => {
            if (treeDone && selfDone) {
                onComplete?.();
            }
        };
        this._nodeContentTree.setOnDisappearedCallback(() => {
            treeDone = true;
            check();
        });
        this._nodeContentTree.homewardNode = null;
        this._nodeContentTree.disappear();
        super.disappearOnlyThisNode(() => {
            selfDone = true;
            check();
        });
    }
}
