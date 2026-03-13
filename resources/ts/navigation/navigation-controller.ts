import type { NavigationRequest, NavigationScope, UrlPolicy } from "./types";
import type { NavigationResult } from "./types";
import { NavigationStateStore } from "./navigation-state-store";
import { NavigationFetcher } from "./navigation-fetcher";
import { HistoryCoordinator } from "./history-coordinator";
import type { CurrentNode } from "../node/current-node";
import type { NodeType } from "../common/type";
import type { DepthSceneController } from "../depth/depth-scene-controller";
import { DepthEffectController } from "../depth/depth-effect-controller";

/**
 * クリック起点のナビゲーション要求を受け取り、
 * scope に応じて disappear / fetch / 適用 を調停する。
 * Phase2: 履歴更新は HistoryCoordinator、結果適用は applyNavigationResult に集約。
 * Phase5: node 更新時に Z 軸演出（playExit → 差し替え → playEnter）を適用。
 */
export class NavigationController
{
    public constructor(
        private _currentNode: CurrentNode,
        private _fetcher: NavigationFetcher,
        private _stateStore: NavigationStateStore,
        private _historyCoordinator: HistoryCoordinator,
        private _depthSceneController: DepthSceneController
    ) {}

    /**
     * アンカーとソースノードから NavigationRequest を組み立てて navigate する。
     * クリック元の sourceNode をそのまま渡すため、ノードに id がなくてもボール追従の消失アニメーションが動作する。
     */
    public navigateFromAnchor(anchor: HTMLAnchorElement, sourceNode: NodeType): void
    {
        const request = this.buildRequestFromAnchor(anchor, sourceNode);
        this.navigate(request, sourceNode);
    }

    /**
     * 既に組み立てた要求でナビゲーションする。（popstate やフォーム送信から利用）
     * @param sourceNodeOverride アンカークリック時など、呼び出し元が持っているソースノード。指定時は getNodeById を使わずこれで消失アニメーションを始める。
     */
    public navigate(request: NavigationRequest, sourceNodeOverride?: NodeType): void
    {
        if (request.scope === 'external') {
            location.href = request.url;
            return;
        }

        this._stateStore.start(request);
        const sourceNode = sourceNodeOverride ?? (request.sourceNodeId
            ? this._currentNode.getNodeById(request.sourceNodeId)
            : null);

        // Phase2: 履歴更新は request 実行前に実施
        if (request.urlPolicy === 'push') {
            this._historyCoordinator.push(request);
        } else if (request.urlPolicy === 'replace') {
            this._historyCoordinator.replace(request);
        }

        if (request.scope === 'full' || request.scope === 'children') {
            this._currentNode.setChildOnly(request.scope === 'children');
            if (sourceNode && 'disappearStart' in sourceNode && typeof sourceNode.disappearStart === 'function') {
                (sourceNode as { disappearStart: () => void }).disappearStart();
            } else {
                this._currentNode.disappear();
            }
            this._fetcher.fetch(request).then(result => {
                this._currentNode.waitUntilDisappeared().then(() => {
                    this._currentNode.applyNavigationResult(result, request);
                    this._stateStore.resolve(result);
                });
            }).catch(err => {
                this.handleNavigationError(request, err, 'ナビゲーション取得に失敗しました');
            });
            return;
        }

        if (request.scope === 'node' && sourceNode) {
            const runAfterDisappear = (): void => {
                this._fetcher.fetch(request).then(result => {
                    this._currentNode.applyNodeResult(result, request);
                    this._stateStore.resolve(result);
                }).catch(err => {
                    this.handleNavigationError(request, err, 'ノード更新の取得に失敗しました');
                });
            };
            const doDisappear = (): void => {
                if ('disappearOnlyThisNode' in sourceNode && typeof sourceNode.disappearOnlyThisNode === 'function') {
                    (sourceNode as { disappearOnlyThisNode: (cb?: () => void) => void }).disappearOnlyThisNode(runAfterDisappear);
                } else {
                    runAfterDisappear();
                }
            };
            if (this._depthSceneController.mode === 'transition' && 'nodeElement' in sourceNode && sourceNode.nodeElement) {
                const el = sourceNode.nodeElement as HTMLElement;
                DepthEffectController.getInstance().playExit(el, 1).then(() => doDisappear());
            } else {
                doDisappear();
            }
            return;
        }

        // fallback: full
        this._currentNode.setChildOnly(false);
        if (sourceNode && 'disappearStart' in sourceNode && typeof sourceNode.disappearStart === 'function') {
            (sourceNode as { disappearStart: () => void }).disappearStart();
        }
        this._fetcher.fetch(request).then(result => {
            this._currentNode.applyNavigationResult(result, request);
            this._stateStore.resolve(result);
        }).catch(err => {
            this.handleNavigationError(request, err, 'ナビゲーション取得に失敗しました');
        });
    }

    private buildRequestFromAnchor(anchor: HTMLAnchorElement, sourceNode: NodeType): NavigationRequest
    {
        let scope: NavigationScope = (anchor.dataset.hgnScope as NavigationScope) ?? this.scopeFromRel(anchor);
        const urlPolicy: UrlPolicy = (anchor.dataset.hgnUrlPolicy as UrlPolicy) ?? 'push';
        if (anchor.target === '_blank') {
            scope = 'external';
        }
        return {
            url: anchor.href,
            scope,
            urlPolicy,
            sourceNodeId: sourceNode.id,
        };
    }

    private scopeFromRel(anchor: HTMLAnchorElement): NavigationScope
    {
        const rel = anchor.getAttribute('rel') ?? '';
        const relTokens = rel
            .split(/\s+/)
            .map(token => token.trim())
            .filter(token => token.length > 0);
        if (relTokens.includes('external')) {
            return 'external';
        }
        if (relTokens.includes('internal-node')) {
            return 'node';
        }
        if (relTokens.includes('internal')) {
            return 'full';
        }
        return 'full';
    }

    private handleNavigationError(request: NavigationRequest, error: unknown, message: string): void
    {
        console.error(message + ':', error);
        this._stateStore.clear();

        // disappear 済みのまま固まるのを防ぐため、通常遷移にフォールバックする。
        if (request.url && request.url.length > 0) {
            location.href = request.url;
        } else {
            location.reload();
        }
    }
}
