import { BasicNode } from "./basic-node";
import { TreeNodeInterface } from "./interface/tree-node-interface";
import { NodeHead } from "./parts/node-head";
import { NodeHeadType } from "../common/type";
import { AppearStatus } from "../enum/appear-status";

/**
 * 「さらに表示」ボタン用ノード。
 * クリックで LinkNode と同じ head-reveal-out でフェードアウトし、
 * その後 data-load-more-url から HTML を取得して親の NodeContentTree に渡す。
 */
export class LoadMoreNode extends BasicNode
{
    private _loading: boolean = false;

    /**
     * コンストラクタ
     */
    public constructor(nodeElement: HTMLElement, parentNode: TreeNodeInterface)
    {
        super(nodeElement, parentNode);

        this._nodeElement.addEventListener('click', this.handleClick.bind(this));
    }

    /**
     * クリック可能だがリンク遷移はしないため、NodeHead のみ使用
     */
    protected loadHead(): NodeHeadType
    {
        const nodeHead = this._nodeElement.querySelector(':scope > .node-head') as HTMLElement;
        return new NodeHead(nodeHead);
    }

    private handleClick(e: MouseEvent): void
    {
        e.preventDefault();

        if (this._loading || !AppearStatus.isAppeared(this._appearStatus)) {
            return;
        }

        const url = this._nodeElement.dataset.loadMoreUrl;
        if (!url) {
            return;
        }

        this._loading = true;

        this._nodeHead.disappear().then(() => {
            return fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            });
        }).then(response => {
            if (!response.ok) {
                throw new Error('Load more request failed');
            }
            return response.text();
        }).then(html => {
            this._parentNode.nodeContentTree.replaceLoadMoreWithNodes(this, html);
        }).catch(() => {
            this._loading = false;
        });
    }
}
