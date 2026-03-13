export type NavigationScope = 'full' | 'children' | 'node' | 'external';

export type UrlPolicy = 'push' | 'keep' | 'replace' | 'popstate';

export type NavigationRequest = {
    url: string;
    scope: NavigationScope;
    urlPolicy: UrlPolicy;
    sourceNodeId?: string;
};

export type NavigationResult = {
    updateType: 'full' | 'children' | 'node' | 'external';
    url: string;
    title: string;
    currentNodeTitle?: string;
    currentNodeContent?: string;
    nodes?: string;
    currentChildrenHtml?: string;
    internalNodeHtml?: string;
    targetNodeId?: string;
    colorState?: string;
    csrfToken?: string;
    components?: { [key: string]: any | null };
};

/** Phase2: 履歴に保存する state（scope と sourceNodeId で popstate 復元） */
export type NavigationHistoryState = {
    url: string;
    scope: 'full' | 'children' | 'node';
    urlPolicy: 'push' | 'replace' | 'popstate';
    sourceNodeId?: string;
};
