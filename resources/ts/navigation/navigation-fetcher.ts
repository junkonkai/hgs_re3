import { Util } from "../common/util";
import type { NavigationRequest } from "./types";
import type { NavigationResult } from "./types";

/**
 * Ajax によるナビゲーション取得。
 * URL に a=1 や internal_node=1 を付与し、レスポンスを NavigationResult に正規化する。
 */
export class NavigationFetcher
{
    /**
     * @param request ナビゲーション要求
     * @returns 正規化された NavigationResult
     */
    public fetch(request: NavigationRequest): Promise<NavigationResult>
    {
        let url = Util.addParameterA(request.url);

        if (request.scope === 'node') {
            const sep = url.includes('?') ? '&' : '?';
            url = url + sep + 'internal_node=1';
            if (request.sourceNodeId) {
                url = url + '&source_node_id=' + encodeURIComponent(request.sourceNodeId);
            }
        }
        if (request.scope === 'children') {
            const sep = url.includes('?') ? '&' : '?';
            url = url + sep + 'children_only=1';
        }

        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.json())
            .then((data: Record<string, unknown>) => this.normalizeResult(data, request));
    }

    /**
     * バックエンドの JSON を NavigationResult に正規化する。
     * updateType が無い場合は request.scope から補う。
     */
    private normalizeResult(data: Record<string, unknown>, request: NavigationRequest): NavigationResult
    {
        const updateType = (data.updateType as NavigationResult['updateType']) ?? this.scopeToUpdateType(request.scope);
        return {
            updateType,
            url: (data.url as string) ?? request.url,
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

    private scopeToUpdateType(scope: NavigationRequest['scope']): NavigationResult['updateType']
    {
        if (scope === 'external') {
            return 'external';
        }
        if (scope === 'children') {
            return 'children';
        }
        if (scope === 'node') {
            return 'node';
        }
        return 'full';
    }
}
