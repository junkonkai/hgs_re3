import type { NavigationRequest } from "./types";
import type { NavigationHistoryState } from "./types";

/**
 * pushState / replaceState / popstate の一元管理。
 * NavigationRequest から履歴 state を生成し、PopStateEvent から NavigationRequest を復元する。
 */
export class HistoryCoordinator
{
    /**
     * urlPolicy === 'push' のとき pushState する。
     */
    public push(request: NavigationRequest): void
    {
        if (request.scope === 'external' || request.urlPolicy === 'keep') {
            return;
        }
        const state = this.requestToState(request);
        window.history.pushState(state, '', request.url);
    }

    /**
     * urlPolicy === 'replace' のとき replaceState する。
     */
    public replace(request: NavigationRequest): void
    {
        if (request.scope === 'external' || request.urlPolicy === 'keep') {
            return;
        }
        const state = this.requestToState(request);
        window.history.replaceState(state, '', request.url);
    }

    /**
     * popstate の state から NavigationRequest を生成する。
     * urlPolicy は 'popstate' になる。
     */
    public createRequestFromPopState(state: NavigationHistoryState | null): NavigationRequest | null
    {
        if (!state?.url) {
            return null;
        }
        return {
            url: state.url,
            scope: state.scope,
            urlPolicy: 'popstate',
            sourceNodeId: state.sourceNodeId,
        };
    }

    private requestToState(request: NavigationRequest): NavigationHistoryState
    {
        const scope = request.scope === 'external' ? 'full' : request.scope;
        const urlPolicy = request.urlPolicy === 'popstate' ? 'push' : request.urlPolicy;
        return {
            url: request.url,
            scope,
            urlPolicy: urlPolicy === 'keep' ? 'push' : urlPolicy,
            sourceNodeId: request.sourceNodeId,
        };
    }
}
