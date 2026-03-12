import { TreeNode } from "./tree-node";
import { TreeNodeInterface } from "./interface/tree-node-interface";
import { AppearStatus } from "../enum/appear-status";
import { BasicNode } from "./basic-node";
import { CurrentNode } from "./current-node";
import { HgnTree } from "../hgn-tree";
import { Util } from "../common/util";
import { ClickableNodeInterface } from "./interface/clickable-node-interface";
import { NodeHeadClickable } from "./parts/node-head-clickable";
import { NodeHeadType } from "../common/type";

export class AccordionTreeNode extends TreeNode implements ClickableNodeInterface
{
    private _groupId: string;
    private _openStatus: AppearStatus;
    private _startScrollY: number;
    private _startPosY: number;

    /**
     * クリック可能なノードヘッダを取得
     */
    public get nodeHead(): NodeHeadClickable
    {
        return this._nodeHead as NodeHeadClickable;
    }


    public constructor(nodeElement: HTMLElement, parentNode: TreeNodeInterface)
    {
        super(nodeElement, parentNode);

        this._groupId = nodeElement.getAttribute('data-accordion-group') || '';
        this._openStatus = AppearStatus.DISAPPEARED;
        this._startScrollY = 0;
        this._startPosY = 0;

        const currentNode = HgnTree.getInstance().currentNode as CurrentNode;
        if (currentNode) {
            currentNode.addAccordionGroup(this._groupId, this);
        }
    }

    public appear(): void
    {
        BasicNode.prototype.appear.call(this);
    }

    public appearAnimation(): void
    {
        BasicNode.prototype.appearAnimation.call(this);
    }

    public open(toggleOtherNodes: boolean = false): void
    {
        if (!AppearStatus.isDisappeared(this._openStatus)) {
            return;
        }
        this._openStatus = AppearStatus.APPEARING;
        this._nodeContentTree.contentElement.classList.add('open');
        this._nodeContentTree.appear(true, true);
        this._appearAnimationFunc = this.openAnimation;
        this._nodeContentBehind?.disappear();
        this._startScrollY = window.scrollY;
        this._startPosY = window.scrollY + this._nodeElement.getBoundingClientRect().top;

        if (toggleOtherNodes) {
            this._toggleOtherNodesInGroup('close');
        }
    }

    public openAnimation(): void
    {
        const nodeRect = this._nodeElement.getBoundingClientRect();
        const posY = nodeRect.top + window.scrollY;
        if (posY !== this._startPosY) {
            window.scrollTo(0, this._startScrollY + (posY - this._startPosY));
        }
        
        if (AppearStatus.isAppeared(this._nodeContentTree.appearStatus)) {
            this._appearAnimationFunc = null;
            this._openStatus = AppearStatus.APPEARED;
        }
        //this.parentNode.resizeConnectionLine();
    }

    public close(): void
    {
        if (!AppearStatus.isAppeared(this._openStatus)) {
            return;
        }
        this._openStatus = AppearStatus.DISAPPEARING;
        
        this._nodeContentTree.disappear(true, true);
        this._appearAnimationFunc = this.closeAnimation;
        this._nodeContentBehind?.appear();
    }


    public closeAnimation(): void
    {
        if (AppearStatus.isDisappeared(this._nodeContentTree.appearStatus)) {
            this._appearAnimationFunc = null;
            this._nodeContentTree.contentElement.classList.remove('open');
            this._openStatus = AppearStatus.DISAPPEARED;
        }
        //this.parentNode.resizeConnectionLine();
    }


    public toggle(): void
    {
        if (AppearStatus.isAppeared(this._openStatus)) {
            this.close();
        } else if (AppearStatus.isDisappeared(this._openStatus)) {
            this.open(true);
        }
    }

    /**
     * 同じアコーディオングループ内の他のノードを指定された状態に切り替える
     * @param action 実行するアクション（'open' または 'close'）
     */
    private _toggleOtherNodesInGroup(action: 'open' | 'close'): void
    {
        const currentNode = HgnTree.getInstance().currentNode as CurrentNode;
        if (currentNode) {
            const group = currentNode.getAccordionGroup(this._groupId);
            for (const node of group) {
                if (node.id !== this.id) {
                    if (action === 'open') {
                        node.open();
                    } else {
                        node.close();
                    }
                }
            }
        }
    }

    /**
     * ホバー時の処理
     */
    public hover(): void
    {
        this._nodeContentBehind?.hover();
        this._animationStartTime = HgnTree.getInstance().timestamp;
        this._updateGradientEndAlphaFunc = this.updateGradientEndAlphaOnHover;
    }

    /**
     * ホバー解除時の処理
     */
    public unhover(): void
    {
        this._nodeContentBehind?.unhover();
        this._animationStartTime = HgnTree.getInstance().timestamp;
        this._updateGradientEndAlphaFunc = this.updateGradientEndAlphaOnUnhover;
    }

    /**
     * クリック時の処理
     * @param e クリックイベント
     */
    public click(e: MouseEvent): void
    {
        this.toggle();
    }
}
