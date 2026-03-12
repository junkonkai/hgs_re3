import type { NavigationResult } from "../../navigation/types";

export class NextNodeCache
{
    public title: string;
    public currentNodeTitle: string;
    public currentNodeContent: string;
    public nodes: string;
    public popup: string;
    public url: string;
    public colorState: string;
    public components: { [key: string]: any | null };
    public csrfToken: string;
    public internalNodeHtml?: string;
    /** Phase2: 差し替え対象ノード id（node 更新時） */
    public targetNodeId?: string;
    /** Phase1: 適用種別（full / children / node） */
    public updateType?: 'full' | 'children' | 'node';
    /** Phase1: 子ノードのみ更新時の HTML（未設定時は nodes を使用） */
    public currentChildrenHtml?: string;

    public constructor()
    {
        this.title = '';
        this.currentNodeTitle = '';
        this.currentNodeContent = '';
        this.nodes = '';
        this.popup = '';
        this.url = '';
        this.colorState = '';
        this.components = {};
        this.csrfToken = '';
    }

    /**
     * NavigationResult から NextNodeCache を生成する。
     */
    public static fromNavigationResult(result: NavigationResult): NextNodeCache
    {
        const cache = new NextNodeCache();
        cache.title = result.title ?? '';
        cache.currentNodeTitle = result.currentNodeTitle ?? '';
        cache.currentNodeContent = result.currentNodeContent ?? '';
        cache.nodes = result.nodes ?? '';
        cache.url = result.url ?? '';
        cache.colorState = result.colorState ?? '';
        cache.components = result.components ?? {};
        cache.csrfToken = result.csrfToken ?? '';
        cache.internalNodeHtml = result.internalNodeHtml;
        cache.targetNodeId = result.targetNodeId;
        cache.updateType = result.updateType !== 'external' ? result.updateType : 'full';
        cache.currentChildrenHtml = result.currentChildrenHtml;
        return cache;
    }
}