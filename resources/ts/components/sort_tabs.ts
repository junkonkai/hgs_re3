import { Component } from "../component";
import { HgnTree } from "../hgn-tree";

/**
 * 並び順タブのアクティブ表示をナビゲーション完了後に切り替える。
 * data-hgn-scope="children" では current-node-content が差し替わらないため JS 側で対応する。
 * アニメーション中はクリックが無効（current-node.ts 側で e.preventDefault()）なので、
 * isNavigating が true になった場合のみ完了を待って切り替える。
 */
export class SortTabs extends Component
{
    private _handlers: Map<HTMLAnchorElement, () => void> = new Map();
    private _tabs: HTMLAnchorElement[] = [];

    constructor(params: any | null = null)
    {
        super(params);

        const containers = Array.from(document.querySelectorAll<HTMLElement>('[data-sort-tabs]'));
        containers.forEach(container => {
            const anchors = Array.from(container.querySelectorAll<HTMLAnchorElement>('a'));
            anchors.forEach(anchor => {
                this._tabs.push(anchor);
                const handler = () => this.handleClick(anchor);
                anchor.addEventListener('click', handler);
                this._handlers.set(anchor, handler);
            });
        });
    }

    private handleClick(clicked: HTMLAnchorElement): void
    {
        // dispose() で _tabs が空になる前にキャプチャしておく。
        const capturedTabs = [...this._tabs];

        requestAnimationFrame(() => {
            if (!HgnTree.getInstance().isNavigating) {
                return;
            }
            this.waitAndActivate(clicked, capturedTabs);
        });
    }

    private waitAndActivate(clicked: HTMLAnchorElement, tabs: HTMLAnchorElement[]): void
    {
        if (!HgnTree.getInstance().isNavigating) {
            this.activate(clicked, tabs);
            return;
        }
        requestAnimationFrame(() => this.waitAndActivate(clicked, tabs));
    }

    private activate(clicked: HTMLAnchorElement, tabs: HTMLAnchorElement[]): void
    {
        tabs.forEach(tab => tab.classList.remove('is-active'));
        clicked.classList.add('is-active');
    }

    public dispose(): void
    {
        this._handlers.forEach((handler, anchor) => {
            anchor.removeEventListener('click', handler);
        });
        this._handlers.clear();
        this._tabs = [];
    }
}
