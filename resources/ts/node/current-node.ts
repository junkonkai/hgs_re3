import { Util } from "../common/util";
import { AppearStatus } from "../enum/appear-status";
import { NodeContentTree } from "./parts/node-content-tree";
import { NodeBase } from "./node-base";
import { TreeNodeInterface } from "./interface/tree-node-interface";
import { NodeType } from "../common/type";
import { AccordionTreeNode } from "./accordion-tree-node";
import { HgnTree } from "../hgn-tree";
import { ComponentManager } from "../component-manager";
import { ScopedHydrator } from "../hydrate/scoped-hydrator";
import type { NavigationRequest } from "../navigation/types";
import type { NavigationResult } from "../navigation/types";
import { DepthEffectController } from "../depth/depth-effect-controller";

export class CurrentNode extends NodeBase implements TreeNodeInterface
{
    private _nodeContentTree: NodeContentTree;

    private _isChanging: boolean = false;
    private _isChildOnly: boolean = false;
    /** Phase6: changeNode 用の保留結果（NextNodeCache 廃止） */
    private _pendingResult: NavigationResult | null = null;
    private _homewardNode: NodeType | null = null;
    private _currentNodeContentElement: HTMLElement | null = null;
    private _accordionGroups: { [key: string]: AccordionTreeNode[] } = {};
    private _tmpStateData: { url: string, isChildOnly: boolean } | null = null;
    private _scopedHydrator: ScopedHydrator = new ScopedHydrator();

    public get homewardNode(): NodeType | null
    {
        return this._homewardNode;
    }

    /**
     * Phase5: DepthSceneController を取得（HgnTree 経由）
     */
    private get depthSceneController()
    {
        return HgnTree.getInstance().depthSceneController;
    }

    /**
     * Phase1: 子ノードのみ更新フラグを設定（NavigationController から呼ぶ）
     */
    public setChildOnly(isChildOnly: boolean): void
    {
        this._isChildOnly = isChildOnly;
    }

    /**
     * Phase1: pushState 用の一時データを設定（NavigationController から呼ぶ）
     */
    public setTmpStateData(data: { url: string; isChildOnly: boolean } | null): void
    {
        this._tmpStateData = data;
    }

    /**
     * コンストラクタ
     * 
     * @param htmlElement HTML要素
     */
    public constructor(htmlElement: HTMLElement)
    {
        super(htmlElement);

        this._currentNodeContentElement = document.getElementById('current-node-content');
        this._nodeContentTree = new NodeContentTree(this._treeContentElement as HTMLElement, this);
        this.setupFormEvents();
    }

    public start(): void
    {
        this._nodeContentTree.loadNodes(this);
        const componentManager = ComponentManager.getInstance();
        componentManager.initializeComponents((window as any).components as { [key: string]: any | null });
        (window as any).components = {};
    }

    /**
     * ノードの開放
     */
    public dispose(): void
    {
        this._nodeContentTree.disposeNodes();
        this._accordionGroups = {};
        this._homewardNode = null;
        document.body.classList.remove('has-error', 'has-warning');

        if (!this._isChildOnly) {
            this._currentNodeContentElement!.innerHTML = '';
        }
    }

    /**
     * ノードコンテンツツリーを取得
     */
    public get nodeContentTree(): NodeContentTree
    {
        return this._nodeContentTree;
    }

    /**
     * Phase3: ノード切替待ちまたはアニメーション進行中か。
     */
    public hasActiveAnimation(): boolean
    {
        if (this._isChanging) {
            return true;
        }
        if (this._appearAnimationFunc !== null) {
            return true;
        }
        return this._nodeContentTree.hasActiveAnimation();
    }

    /**
     * Phase3: アニメーション開始時に Scheduler を起動する。
     */
    public requestAnimationFrameIfNeeded(): void
    {
        const hgn = HgnTree.getInstance();
        if (typeof (hgn as { requestAnimationFrameIfNeeded?: () => void }).requestAnimationFrameIfNeeded === 'function') {
            (hgn as { requestAnimationFrameIfNeeded: () => void }).requestAnimationFrameIfNeeded();
        }
    }

    public resize(): void
    {
        super.resize();
        this._nodeContentTree.resize();
    }

    /**
     * 更新
     */
    public update(): void
    {
        if (this._isChanging) {
            // ノード切り替え待ち
            this.changeNode();
        } else {
            this._appearAnimationFunc?.();

            super.update();

            this._nodeContentTree.update();
        }
    }

    /**
     * 描画
     */
    public draw(): void
    {
        super.draw();

        this._nodeContentTree.draw();
    }

    /**
     * 出現アニメーション開始
     */
    public appear(): void
    {
        this.requestAnimationFrameIfNeeded();
        HgnTree.getInstance().calculateDisappearSpeedRate(1);

        this._appearStatus = AppearStatus.APPEARING;
        if (!this._isChildOnly) {
            this._nodeHead.appear();
        }

        this.appearContents();
        this._nodeContentTree.appear();

        const connectionPoint = this._nodeHead.getConnectionPoint();
        this.freePt.setPos(connectionPoint.x, connectionPoint.y).setElementPos();
        this.freePt.show();

        this._appearAnimationFunc = this.appearAnimation;

    }

    /**
     * 出現アニメーション
     */
    private appearAnimation(): void
    {
        if (AppearStatus.isAppeared(this._nodeContentTree.appearStatus)) {
            this._appearAnimationFunc = null;
            this._appearStatus = AppearStatus.APPEARED;
        }
    }

    /**
     * 消滅アニメーション準備
     * 
     * @param homewardNode クリックしたノード（帰路の起点）
     */
    public prepareDisappear(homewardNode: NodeType): void
    {
        // クリックしたリンクノードから親をたどってCurrentNodeにたどり着く
        // ここに来たらdisappearを呼ぶ
        this._homewardNode = homewardNode;
        this._nodeContentTree.homewardNode = homewardNode;
        this.disappear();
    }

    /**
     * 消滅アニメーション開始
     */
    public disappear(): void
    {
        this.requestAnimationFrameIfNeeded();
        this._appearStatus = AppearStatus.DISAPPEARING;
        this._nodeContentTree.disappear();

        if (this._homewardNode !== null) {
            this._appearAnimationFunc = this.disappearAnimation;
        } else {
            this.disappearHeader();
            
            this._appearAnimationFunc = this.disappearAnimationWaitComplete;
        }
    }

    /**
     * 消滾アニメーション
     */
    private disappearAnimation(): void
    {
        if (AppearStatus.isDisappeared(this._nodeContentTree.lastNode.appearStatus)) {
            this._appearAnimationFunc = this.disappearAnimationWaitComplete;
        }
    }

    public disappearHeader(): void
    {
        if (!this._isChildOnly) {
            this._nodeHead.disappear();
            this.disappearContents();
        }
    }

    private disappearAnimationWaitComplete(): void
    {
        if ((this._isChildOnly || AppearStatus.isDisappeared(this._nodeHead.appearStatus)) &&
            AppearStatus.isDisappeared(this._nodeContentTree.appearStatus)) {
            this._appearAnimationFunc = null;
            this.disappeared();
        }
    }

    /**
     * 消滅完了
     */
    public disappeared(): void
    {
        window.scrollTo(0, 0);

        this._appearStatus = AppearStatus.DISAPPEARED;

        this._isChanging = true;
    }

    /**
     * ノードの切り替え（Phase6: NavigationResult を直接適用、NextNodeCache 廃止）
     */
    private changeNode(): void
    {
        if (!this._pendingResult || this._appearStatus !== AppearStatus.DISAPPEARED) {
            return;
        }
        const result = this._pendingResult;
        this._pendingResult = null;

        if (this._tmpStateData) {
            if (result.url && result.url.length > 0) {
                this._tmpStateData.url = result.url;
            }
            window.history.pushState(this._tmpStateData, '', this._tmpStateData.url);
            this._tmpStateData = null;
        }

        const updateType = result.updateType ?? 'full';
        if (updateType === 'children') {
            this.applyChildrenResult(result, { url: result.url, scope: 'children', urlPolicy: 'push' });
        } else {
            this.applyFullResult(result, { url: result.url, scope: 'full', urlPolicy: 'push' });
        }
    }

    /**
     * Phase6: fetchForMove / postData 用。保留結果をセットする。
     */
    private setPendingNavigationResult(result: NavigationResult): void
    {
        this._pendingResult = result;
    }

    /**
     * フォームのイベントを設定する
     */
    private setupFormEvents(): void
    {
        if (this._currentNodeContentElement) {
            const forms = Array.from(this._currentNodeContentElement.querySelectorAll('form')) as HTMLFormElement[];
            forms.forEach(form => {
                // コンポーネント側で処理するやつは無視
                if (form.dataset.componentUse === '1') {
                    return;
                }

                form.addEventListener('submit', (e) => {
                    this.submitCurrentNodeContentForm(form, e);
                    return false;
                });
            });
        }
    }

    /**
     * フォームの送信
     * 
     * @param form 送信したフォーム
     * @param e 送信イベント
     */
    private submitCurrentNodeContentForm(form: HTMLFormElement, e: SubmitEvent): void
    {
        e.preventDefault();

        if (!AppearStatus.isAppeared(this._nodeContentTree.appearStatus)) {
            return;
        }

        const isChildOnly = form.dataset.childOnly === '1';

        if (form.method.toUpperCase() !== 'POST') {
            const params = new URLSearchParams(new FormData(form) as any);
            this.moveNode(form.action + '?' + params.toString(), false, isChildOnly);
        } else {
            const isNoPushState = form.dataset.noPushState === '1';
            const formData = new FormData(form);
            this.changeChildNodesWithData(form.action, formData, isChildOnly, isNoPushState);
        }

        this.disappear();
    }



    /**
     * 別のノードへ移動する（Phase1: 通常時は NavigationController に委譲、popstate 時は従来の fetch）
     *
     * @param url 取得 URL
     * @param isFromPopState 履歴復元の場合は true
     * @param isChildOnly 子ノードのみ更新の場合は true
     */
    public moveNode(url: string, isFromPopState: boolean, isChildOnly: boolean = false): void
    {
        const nav = HgnTree.getInstance().navigationController;
        if (!isFromPopState && nav) {
            nav.navigate({
                url,
                scope: isChildOnly ? 'children' : 'full',
                urlPolicy: 'push',
            });
            return;
        }

        this._isChildOnly = isChildOnly;
        if (!isFromPopState) {
            this._tmpStateData = { url, isChildOnly };
        } else {
            this._tmpStateData = null;
        }
        this.fetchForMove(url);
    }

    /**
     * Phase6: popstate または NavigationController 未設定時の fetch。保留結果をセット。
     */
    private fetchForMove(url: string): void
    {
        const urlWithParam = this.buildTreeFetchUrl(url);
        fetch(urlWithParam, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.json())
            .then((data: Record<string, unknown>) => {
                this.setPendingNavigationResult(this.normalizeFetchResult(data));
            })
            .catch(error => {
                console.error('データの取得に失敗しました:', error);
            });
    }

    private buildTreeFetchUrl(url: string): string
    {
        return Util.addParameterA(url);
    }

    private normalizeFetchResult(data: Record<string, unknown>): NavigationResult
    {
        return {
            updateType: (data.updateType as NavigationResult['updateType']) ?? 'full',
            url: (data.url as string) ?? '',
            title: (data.title as string) ?? '',
            currentNodeTitle: data.currentNodeTitle as string | undefined,
            currentNodeContent: data.currentNodeContent as string | undefined,
            nodes: data.nodes as string | undefined,
            currentChildrenHtml: data.currentChildrenHtml as string | undefined,
            internalNodeHtml: data.internalNodeHtml as string | undefined,
            targetNodeId: data.targetNodeId as string | undefined,
            colorState: data.colorState as string | undefined,
            csrfToken: data.csrfToken as string | undefined,
            components: data.components as { [key: string]: any | null } | undefined,
        };
    }

    public postData(url: string, data: any, isChildOnly: boolean = false, isNoPushState: boolean = false): void
    {
        if (!isNoPushState) {
            this._tmpStateData = { url: url, isChildOnly: isChildOnly };
        } else {
            this._tmpStateData = null;
        }
        this._isChildOnly = isChildOnly;

        const urlWithParam = Util.addParameterA(url);
        fetch(urlWithParam, {
            headers: {"X-Requested-With": "XMLHttpRequest"},
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then((data: Record<string, unknown>) => {
            this.setPendingNavigationResult(this.normalizeFetchResult(data));
        })
        .catch(error => {
            console.error('データの送信に失敗しました:', error);
        });
    }

    public changeChildNodes(url: string, nodeId: string | null, isChildOnly: boolean = false): void
    {
        if (nodeId) {
            const node = this.getNodeById(nodeId);
            if (node && !(node instanceof CurrentNode)) {
                node.parentNode.prepareDisappear(node);
            }
        }

        this.moveNode(url, false, isChildOnly);
        this.disappear();
    }

    public changeChildNodesWithData(url: string, data: any, isChildOnly: boolean = false, isNoPushState: boolean = false): void
    {
        this._isChildOnly = false;
        this.postData(url, data, isChildOnly, isNoPushState);
        this.disappear();
    }

    public homewardDisappear(): void
    {
        this._nodeContentTree.disappearConnectionLine();
    }

    public resizeConnectionLine(): void
    {
        this._nodeContentTree.resizeConnectionLine(this._nodeHead.getConnectionPoint());
    }

    public addAccordionGroup(groupId: string, node: AccordionTreeNode): void
    {
        if (!this._accordionGroups[groupId]) {
            this._accordionGroups[groupId] = [];
        }
        this._accordionGroups[groupId].push(node);
    }

    public getAccordionGroup(groupId: string): AccordionTreeNode[]
    {
        return this._accordionGroups[groupId];
    }

    public getNodeById(id: string): NodeType | null
    {
        return this._nodeContentTree.getNodeById(id);
    }

    /**
     * Phase2: NavigationResult を request に応じて適用する。履歴更新は NavigationController 側で実施済み。
     */
    public applyNavigationResult(result: NavigationResult, request: NavigationRequest): void
    {
        const updateType = result.updateType ?? 'full';
        if (updateType === 'children') {
            this.applyChildrenResult(result, request);
            return;
        }
        if (updateType === 'node') {
            this.applyNodeResult(result, request);
            return;
        }
        this.applyFullResult(result, request);
    }

    /**
     * 全体更新（Phase6: changeNode からも NavigationResult で直接呼ぶ）
     */
    private applyFullResult(result: NavigationResult, _request: NavigationRequest): void
    {
        const componentManager = ComponentManager.getInstance();
        componentManager.disposeComponents();
        this.dispose();

        if (result.colorState) {
            document.body.classList.add('has-' + result.colorState);
        }
        if (result.csrfToken && result.csrfToken.length > 0) {
            (window as any).Laravel.csrfToken = result.csrfToken;
        }

        if (!this._isChildOnly) {
            document.title = result.title + ' | ' + (window as any).siteName;
            this._nodeHead.title = result.currentNodeTitle ?? '';
            if (this._currentNodeContentElement && result.currentNodeContent) {
                this._currentNodeContentElement.innerHTML = result.currentNodeContent;
                this.setupFormEvents();
            }
        }
        if (this._treeContentElement && result.nodes) {
            this._treeContentElement.innerHTML = result.nodes;
        }
        this._nodeContentTree.loadNodes(this);
        this.resize();
        this._scopedHydrator.hydrate(this._treeContentElement as HTMLElement, result.components);

        this._isChanging = false;
        this.appear();
    }

    /**
     * Phase2: 子ノードのみ差し替え。replaceChildren 経由で正式に差分更新。
     */
    private applyChildrenResult(result: NavigationResult, _request: NavigationRequest): void
    {
        const componentManager = ComponentManager.getInstance();
        componentManager.disposeComponents();
        this._accordionGroups = {};
        this._homewardNode = null;

        if (result.colorState) {
            document.body.classList.add('has-' + result.colorState);
        }
        if (result.csrfToken && result.csrfToken.length > 0) {
            (window as any).Laravel.csrfToken = result.csrfToken;
        }

        const html = result.currentChildrenHtml ?? result.nodes ?? '';
        let newNodes: NodeType[] = [];
        if (this._treeContentElement && html) {
            newNodes = this._nodeContentTree.replaceChildren(html);
        }
        this.resize();
        if (this.depthSceneController.mode === 'transition' && newNodes.length > 0) {
            const dec = DepthEffectController.getInstance();
            newNodes.forEach(node => dec.playEnter(node.nodeElement, 1));
        }
        this._scopedHydrator.hydrate(this._treeContentElement as HTMLElement, result.components);

        this._isChanging = false;
        this._nodeContentTree.appear();
    }

    /**
     * Phase2: 選択ノード 1 個を差し替え。replaceNodeById 経由で正式に差分更新。
     */
    public applyNodeResult(result: NavigationResult, request: NavigationRequest): void
    {
        const html = result.internalNodeHtml;
        if (!html || typeof html !== 'string') {
            return;
        }
        const targetId = result.targetNodeId ?? request.sourceNodeId;
        if (!targetId) {
            return;
        }
        const newNode = this._nodeContentTree.replaceNodeById(targetId, html);
        if (!newNode) {
            return;
        }
        if (this.depthSceneController.mode === 'transition') {
            DepthEffectController.getInstance().playEnter(newNode.nodeElement, 1);
        }
        if ('appear' in newNode && typeof newNode.appear === 'function') {
            (newNode as { appear: (a?: boolean, b?: boolean) => void }).appear(true, true);
        }
        this.resizeConnectionLine();
        this._scopedHydrator.hydrate(newNode.nodeElement, result.components);
    }

    /**
     * Phase5: persistent モード時、子ツリー全体に depth を適用する。
     */
    public applyDepthToTree(): void
    {
        if (this.depthSceneController.mode !== 'persistent') {
            return;
        }
        this._nodeContentTree.applyDepthToNodes(0);
    }

    /**
     * rel="internal-node" 用: 指定ノード内のみ disappear → 取得 → DOM 差し替え → 再構築 → appear
     * （Phase1 では NavigationController + applyNodeResult に委譲するため、主に後方互換）
     *
     * @param url 取得 URL（a=1&internal_node=1 を付与して fetch）
     * @param clickedNode クリックされたノード（差し替え対象の section.node）
     */
    public updateSingleNode(url: string, clickedNode: NodeType): void
    {
        if (!('parentNode' in clickedNode)) {
            return;
        }
        const parent = (clickedNode as { parentNode: TreeNodeInterface }).parentNode;
        const tree = parent.nodeContentTree;
        const nodeIndex = tree.getIndexByNode(clickedNode);
        if (nodeIndex < 0) {
            return;
        }

        const runFetch = (): void => {
            const urlWithA = Util.addParameterA(url);
            const sep = urlWithA.includes('?') ? '&' : '?';
            const fetchUrl = urlWithA + sep + 'internal_node=1';

            fetch(fetchUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(response => response.json())
                .then((data: { internalNodeHtml?: string }) => {
                    const html = data.internalNodeHtml;
                    if (!html || typeof html !== 'string') {
                        return;
                    }
                    const temp = document.createElement('div');
                    temp.innerHTML = html.trim();
                    const newSection = temp.firstElementChild as HTMLElement;
                    if (!newSection) {
                        return;
                    }
                    const oldSection = clickedNode.nodeElement;
                    const parentEl = oldSection.parentNode;
                    if (!parentEl) {
                        return;
                    }
                    parentEl.replaceChild(newSection, oldSection);
                    tree.disposeNodes();
                    tree.loadNodes(parent);
                    const newNode = tree.getNodeByIndex(nodeIndex);
                    if (newNode) {
                        newNode.appear(true, true);
                    }
                    this.resizeConnectionLine();
                    if (url) {
                        window.history.pushState({ url, isInternalNode: true, nodeId: clickedNode.id }, '', url);
                    }
                })
                .catch(err => {
                    console.error('internal-node 取得に失敗しました:', err);
                });
        };

        if ('disappearOnlyThisNode' in clickedNode && typeof clickedNode.disappearOnlyThisNode === 'function') {
            clickedNode.disappearOnlyThisNode(runFetch);
        } else {
            runFetch();
        }
    }
}

