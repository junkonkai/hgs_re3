import { HgnTree } from "../../hgn-tree";
import { AppearStatus } from "../../enum/appear-status";
import { CurrentNode } from "../current-node";
import { Util } from "../../common/util";
import { Point } from "../../common/point";
import { NodeHeadClickable } from "../parts/node-head-clickable";

/**
 * LinkNodeとLinkTreeNodeの共通機能を提供するmixin
 */
export class LinkNodeMixin
{
    //public _isHomewardDisappear: boolean = false;
    private _parentInstance: any;

    constructor(parentInstance: any)
    {
        this._parentInstance = parentInstance;
    }

    public get anchor(): HTMLAnchorElement
    {
        const titleElement = this._parentInstance._nodeHead.titleElement;
        
        // titleElementが<a>タグの場合はそのまま返す
        if (titleElement instanceof HTMLAnchorElement) {
            return titleElement;
        }
        
        // titleElementが<span>などの場合は、子要素の<a>タグを探す
        const anchor = titleElement.querySelector('a');
        if (anchor) {
            return anchor;
        }
        
        // フォールバック: 型アサーションで返す（通常はここには到達しない）
        return titleElement as HTMLAnchorElement;
    }

    public get nodeHead(): NodeHeadClickable
    {
        return this._parentInstance._nodeHead as NodeHeadClickable;
    }

    /**
     * ホバー時の処理
     */
    public hover(): void
    {
        this._parentInstance._nodeContentBehind?.hover();
        this._parentInstance._animationStartTime = HgnTree.getInstance().timestamp;
        this._parentInstance._updateGradientEndAlphaFunc = this._parentInstance.updateGradientEndAlphaOnHover;
    }

    /**
     * ホバー解除時の処理
     */
    public unhover(): void
    {
        this._parentInstance._nodeContentBehind?.unhover();
        this._parentInstance._animationStartTime = HgnTree.getInstance().timestamp;
        this._parentInstance._updateGradientEndAlphaFunc = this._parentInstance.updateGradientEndAlphaOnUnhover;
    }

    /**
     * クリック時の処理
     * @param e クリックイベント
     */
    public click(e: MouseEvent): void
    {
        this._parentInstance.clickLink(this.anchor, e);
    }
}
