import { Point } from "../../common/point";
import { ConnectionLine } from "./connection-line";
import { LinkNode } from "../link-node";
import { AppearStatus } from "../../enum/appear-status";
import { NodeType } from "../../common/type";
import { NodeContent } from "./node-content";
import { TreeNode } from "../tree-node";
import { TreeNodeInterface } from "../interface/tree-node-interface";
import { BasicNode } from "../basic-node";
import { AccordionTreeNode } from "../accordion-tree-node";
import { LinkTreeNode } from "../link-tree-node";
import { LoadMoreNode } from "../load-more-node";
import { CurrentNode } from "../current-node";

export class NodeContentTree extends NodeContent
{
    protected _parentNode: NodeType;
    protected _nodes: NodeType[];
    protected _connectionLine: ConnectionLine;
    protected _connectionLineFadeOut: ConnectionLine;

    protected _appearStatus: AppearStatus;
    public homewardNode: NodeType | null;
    public appearAnimationFunc: (() => void) | null;
    protected _nodeCount: number;
    protected _isFast: boolean;
    protected _doNotAppearBehind: boolean;
    protected _onDisappearedCallback: (() => void) | null = null;

    public get appearStatus(): AppearStatus
    {
        return this._appearStatus;
    }

    /**
     * 接続線を取得
     * 
     * @returns 接続線
     */
    public get connectionLine(): ConnectionLine
    {
        return this._connectionLine;
    }

    /**
     * コンストラクタ
     * 
     * @param nodeElement 
     */
    public constructor(nodeElement: HTMLElement, parentNode: NodeType)
    {
        super(nodeElement);

        this._parentNode = parentNode;

        const connectionLineElement = ConnectionLine.createElement();
        nodeElement.parentNode?.append(connectionLineElement);
        this._connectionLine = new ConnectionLine(connectionLineElement);

        const connectionLineFadeOutElement = ConnectionLine.createElement();
        nodeElement.parentNode?.append(connectionLineFadeOutElement);
        this._connectionLineFadeOut = new ConnectionLine(connectionLineFadeOutElement);

        this._nodes = [];

        this._appearStatus = AppearStatus.DISAPPEARED;
        this.homewardNode = null;
        this.appearAnimationFunc = null;
        this._nodeCount = 0;
        this._isFast = false;
        this._doNotAppearBehind = false;
    }

    /**
     * ノードの読み込み
     */
    public loadNodes(parentNode: TreeNodeInterface): void
    {
        this._nodeCount = 0;
        this.homewardNode = null;
        this._contentElement.querySelectorAll(':scope > section.node').forEach(nodeElement => {
            this._nodes.push(this.createNodeFromElement(nodeElement as HTMLElement, parentNode));
            this._nodeCount++;
        });
    }

    /**
     * 要素からノードオブジェクトを1つ生成する（loadNodes と replaceLoadMoreWithNodes で共通利用）
     */
    private createNodeFromElement(nodeElement: HTMLElement, parentNode: TreeNodeInterface): NodeType
    {
        if (nodeElement.classList.contains('link-node')) {
            return new LinkNode(nodeElement, parentNode);
        }
        if (nodeElement.classList.contains('link-tree-node')) {
            return new LinkTreeNode(nodeElement, parentNode);
        }
        if (nodeElement.classList.contains('load-more-node')) {
            return new LoadMoreNode(nodeElement, parentNode);
        }
        if (nodeElement.classList.contains('tree-node')) {
            if (nodeElement.classList.contains('accordion')) {
                return new AccordionTreeNode(nodeElement, parentNode);
            }
            return new TreeNode(nodeElement, parentNode);
        }
        return new BasicNode(nodeElement, parentNode);
    }

    /**
     * Phase2: CurrentNode 直下の子ノード群を全部差し替える。
     * 旧ノードを dispose し、新しい section.node 群を生成して返す。
     */
    public replaceChildren(html: string): NodeType[]
    {
        this.disposeNodes();
        this._contentElement.innerHTML = html.trim();
        this.loadNodes(this._parentNode as TreeNodeInterface);
        this.resizeConnectionLine(this._parentNode.nodeHead.getConnectionPoint());
        return this._nodes;
    }

    /**
     * Phase2: 指定 id のノード 1 個を差し替える。見つからなければ子 TreeNode に再帰委譲。
     * 置換後の新ノードを返す。見つからない場合は null。
     */
    public replaceNodeById(nodeId: string, html: string): NodeType | null
    {
        const index = this._nodes.findIndex(n => n.id === nodeId);
        if (index >= 0) {
            const oldNode = this._nodes[index];
            const parentEl = oldNode.nodeElement.parentNode;
            if (!parentEl) {
                return null;
            }
            oldNode.dispose();
            const temp = document.createElement('div');
            temp.innerHTML = html.trim();
            const newSection = temp.firstElementChild as HTMLElement;
            if (!newSection) {
                return null;
            }
            parentEl.replaceChild(newSection, oldNode.nodeElement);
            const newNode = this.createNodeFromElement(newSection, this._parentNode as TreeNodeInterface);
            this._nodes[index] = newNode;
            this.resizeConnectionLine(this._parentNode.nodeHead.getConnectionPoint());
            return newNode;
        }
        for (const n of this._nodes) {
            if ('nodeContentTree' in n && n.nodeContentTree) {
                const found = (n as TreeNodeInterface).nodeContentTree.replaceNodeById(nodeId, html);
                if (found) {
                    return found;
                }
            }
        }
        return null;
    }

    /**
     * 「さらに表示」クリック後: 取得した HTML を LoadMoreNode の位置に挿入し、追加ノードを登録・接続線を伸ばし・追加分のみ appear させる。
     */
    public replaceLoadMoreWithNodes(loadMoreNode: LoadMoreNode, html: string): void
    {
        const contentElement = this._contentElement;
        const loadMoreElement = loadMoreNode.nodeElement;

        const temp = document.createElement('div');
        temp.innerHTML = html.trim();

        const insertedElements: HTMLElement[] = [];
        while (temp.firstElementChild) {
            const child = temp.firstElementChild as HTMLElement;
            contentElement.insertBefore(child, loadMoreElement);
            insertedElements.push(child);
        }

        loadMoreElement.remove();

        const loadMoreIndex = this._nodes.indexOf(loadMoreNode);
        if (loadMoreIndex === -1) {
            return;
        }

        this._nodes.splice(loadMoreIndex, 1);
        this._nodeCount = this._nodes.length;

        insertedElements.forEach((el, i) => {
            const node = this.createNodeFromElement(el, this._parentNode as TreeNodeInterface);
            this._nodes.splice(loadMoreIndex + i, 0, node);
        });
        this._nodeCount = this._nodes.length;

        this.resizeConnectionLine(this._parentNode.nodeHead.getConnectionPoint());

        const startIndex = loadMoreIndex;
        const endIndex = loadMoreIndex + insertedElements.length;
        for (let i = startIndex; i < endIndex; i++) {
            const node = this._nodes[i];
            if (AppearStatus.isDisappeared(node.appearStatus)) {
                node.appear(true, true);
            }
        }
    }

    public get lastNode(): NodeType
    {
        return this._nodes[this._nodes.length - 1];
    }

    /**
     * 子ノードが存在する場合のみ lastNode を返す（空のときの undefined 参照を防ぐ）
     */
    public get lastNodeOrNull(): NodeType | null
    {
        return this._nodes.length > 0 ? this._nodes[this._nodes.length - 1] : null;
    }

    public getNodeByIndex(index: number): NodeType
    {
        return this._nodes[index];
    }

    /**
     * ノードのインデックスを取得（internal-node 更新時の差し替え位置の特定に利用）
     */
    public getIndexByNode(node: NodeType): number
    {
        return this._nodes.indexOf(node);
    }

    /**
     * Phase3: 接続線または子ノードに進行中アニメーションがあるか。
     */
    public hasActiveAnimation(): boolean
    {
        if (AppearStatus.isTransitioning(this._connectionLine.appearStatus)) {
            return true;
        }
        if (this.appearAnimationFunc !== null) {
            return true;
        }
        return this._nodes.some(n => (n as { hasActiveAnimation?: () => boolean }).hasActiveAnimation?.() === true);
    }

    public getNodeById(id: string): NodeType | null
    {
        let node = this._nodes.find(node => node.id === id) || null;
        if (node) {
            return node;
        }

        for (const n of this._nodes) {
            // nodeがTreeNodeInterfaceを実装している場合
            if ('getNodeById' in n && typeof n.getNodeById === 'function') {
                node = (n as TreeNodeInterface).getNodeById(id);
                if (node) {
                    return node;
                }
            }
        }
        return null;
    }

    /**
     * ノードの開放
     */
    public disposeNodes(): void
    {
        this._nodes.forEach(node => {
            if (node) {
                node.dispose();
            }
        });
        this._nodes = [];
    }

    public resize(): void
    {
        this._nodes.forEach(node => {
            if (node) {
                node.resize();
            }
        });
        this.resizeConnectionLine(this._parentNode.nodeHead.getConnectionPoint());
    }

    public resizeConnectionLine(headerPosition: Point): void
    {
        if (this._connectionLine && !AppearStatus.isDisappeared(this._connectionLine.appearStatus) && this._nodes.length > 0) {
            this._connectionLine.setPosition(headerPosition.x - 1, headerPosition.y);
            this._connectionLine.changeHeight(this.lastNode.nodeElement.offsetTop - headerPosition.y + 2);
        }
    }


    public update(): void
    {
        this._connectionLine.update();
        this._nodes.forEach(node => {
            if (node) {
                node.update();
            }
        });

        if (this.appearAnimationFunc !== null) {
            this.appearAnimationFunc();
        }
    }

    public appear(isFast: boolean = false, doNotAppearBehind: boolean = false): void
    {
        if (this._nodes.length === 0) {
            this._appearStatus = AppearStatus.APPEARED;
            this.appearAnimationFunc = null;
            return;
        }
        const headerPosition = this._parentNode.nodeHead.getConnectionPoint();
        this._connectionLine.setPosition(headerPosition.x - 1, headerPosition.y);
        const conLineHeight = this.lastNode.nodeElement.offsetTop - headerPosition.y + 2;
        this._connectionLine.changeHeight(conLineHeight);
        this._connectionLine.appear(isFast);

        this._appearStatus = AppearStatus.APPEARING;
        this.appearAnimationFunc = this.appearAnimation;
        this._isFast = isFast;
    }

    /**
     * 出現アニメーション
     */
    public appearAnimation(): void
    {
        const headerPosition = this._parentNode.nodeHead.getConnectionPoint();
        const conLineHeight = this._connectionLine.getAnimationHeight();
        const freePt = this._parentNode.freePt;
        freePt.moveOffset(0, conLineHeight);
        
        this._nodes.forEach(node => {
            if (!node) {
                return;
            }
            if (AppearStatus.isDisappeared(node.appearStatus)) {
                const top = node.nodeElement.offsetTop - headerPosition.y;
                if (top <= conLineHeight) {
                    node.appear(this._isFast, this._doNotAppearBehind);
                }
            }
        });

        if (AppearStatus.isAppeared(this._connectionLine.appearStatus)) {
            freePt.hide();
            this.appearAnimationFunc = this.appearAnimation2;
        }
    }

    public appearAnimation2(): void
    {
        const last = this._nodes.length > 0 ? this.lastNode : null;
        if (last && last.appearStatus === AppearStatus.APPEARED) {
            this.appeared();
        }
    }

    public appeared(): void
    {
        this._appearStatus = AppearStatus.APPEARED;
        this.appearAnimationFunc = null;
    }

    public disappear(isFast: boolean = false, doNotAppearBehind: boolean = false): void
    {
        this._isFast = isFast;
        this._doNotAppearBehind = doNotAppearBehind;
        if (this.homewardNode) {
            const headerPosition = this._parentNode.nodeHead.getConnectionPoint();
            const height = this.homewardNode.nodeElement.offsetTop - headerPosition.y + 2;
            const orgHeight = this._connectionLine.height;
            this._connectionLine.changeHeight(height);
            this._connectionLineFadeOut.setPosition(headerPosition.x - 1, headerPosition.y + height);
            this._connectionLineFadeOut.changeHeight(orgHeight - height);
            this._connectionLineFadeOut.visible();
            this._connectionLineFadeOut.disappearFadeOut();
            this.disappeareUnderLine(height, headerPosition);
            this.homewardNode.disappear();

            this._appearStatus = AppearStatus.DISAPPEARING;
            this.appearAnimationFunc = this.disappearAnimation;
            const freePt = this._parentNode.freePt;
            freePt.setPos(headerPosition.x, headerPosition.y);
            freePt.moveOffset(0, height);
        } else {
            this._connectionLine.disappearFadeOut(isFast);
            this._appearStatus = AppearStatus.DISAPPEARING;
            this.appearAnimationFunc = this.disappearAnimation2;
            this._nodes.forEach(node => {
                if (node) {
                    node.disappear(this._isFast, this._doNotAppearBehind);
                }
            });
        }
    }

    private disappeareUnderLine(conLineHeight: number, headerPosition: Point): void
    {
        this._nodes.forEach(node => {
            if (!node) {
                return;
            }
            if (AppearStatus.isAppeared(node.appearStatus)) {
                const top = node.nodeElement.offsetTop - headerPosition.y;
                if (top >= conLineHeight) {
                    node.disappear(this._isFast, this._doNotAppearBehind);
                }
            }
        });
    }

    /**
     * 消失アニメーション
     */
    public disappearAnimation(): void
    {
        const freePt = this._parentNode.freePt;
        const headerPosition = this._parentNode.nodeHead.getConnectionPoint();
        if (AppearStatus.isDisappearing(this._connectionLine.appearStatus)) {
            const conLineHeight = this._connectionLine.getAnimationHeight();
            this.disappeareUnderLine(conLineHeight - 100, headerPosition);
            if (conLineHeight <= 70) {
                if (this._parentNode instanceof CurrentNode) {
                    this._parentNode.disappearHeader();
                } else {
                    this._parentNode.nodeHead.disappear();
                }
            }

            freePt.moveOffset(0, conLineHeight);
            this.disappearScroll();
        } else if (AppearStatus.isDisappeared(this._connectionLine.appearStatus)) {
            this.disappeareUnderLine(0, headerPosition);
            // ヘッダーが出現中だったら消す
            if (AppearStatus.isAppeared(this._parentNode.nodeHead.appearStatus)) {
                if (this._parentNode instanceof CurrentNode) {
                    this._parentNode.disappearHeader();
                } else {
                    this._parentNode.nodeHead.disappear();
                }
            }
            this.disappeared();
            freePt.hide();
        }
    }

    private disappearScroll(): void
    {
        const rect = this._connectionLine.element.getBoundingClientRect();
        const elementTop = rect.top + window.scrollY + this._connectionLine.getAnimationHeight();
        const scrollY = window.scrollY;
        const windowHeight = window.innerHeight;
        const screenCenter = scrollY + windowHeight / 2;
        
        // 要素が画面中央より上にある場合、スクロール位置を調整
        if (elementTop < screenCenter) {
            window.scrollTo(0, elementTop - windowHeight / 2);
        }
    }

    public disappearAnimation2(): void
    {
        if (AppearStatus.isDisappeared(this._connectionLine.appearStatus)) {
            this.disappeared();
        }
    }

    public disappeared(): void
    {
        this._appearStatus = AppearStatus.DISAPPEARED;
        this.appearAnimationFunc = null;
        if (this._onDisappearedCallback) {
            const cb = this._onDisappearedCallback;
            this._onDisappearedCallback = null;
            cb();
        }
    }

    /**
     * 消滅完了時に呼ばれるコールバックを設定（internal-node 用）
     */
    public setOnDisappearedCallback(cb: (() => void) | null): void
    {
        this._onDisappearedCallback = cb;
    }

    public disappearConnectionLine(): void
    {
        if (!AppearStatus.isDisappeared(this._connectionLine.appearStatus)) {
            this._parentNode.freePt.show();
            this.connectionLine.disappear(0);
        }
    }

    public draw(): void
    {
        this._nodes.forEach(node => {
            if (node) {
                node.draw();
            }
        });
    }

    public noDisplay(): void
    {
        this._contentElement.style.display = 'none';
    }

    public display(): void
    {
        this._contentElement.style.display = 'block';
    }
}