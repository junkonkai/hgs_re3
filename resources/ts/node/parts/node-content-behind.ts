import { NodeContent } from "./node-content";
import { BehindNode } from "./behind-node";
import { AppearStatus } from "../../enum/appear-status";
import { Util } from "../../common/util";
import { Point } from "../../common/point";
import { CurveRenderer } from "./renderers/curve-renderer";

export class NodeContentBehind extends NodeContent
{
    private _behindNodes: BehindNode[];
    private _animationStartTime: number;
    private _curveAppearProgress: number[];
    private _appearStatus: AppearStatus;
    private _appearAnimationFunc: (() => void) | null;
    public _maxEndOpacity: number;
    public _minEndOpacity: number;

    public get appearStatus(): AppearStatus
    {
        return this._appearStatus;
    }

    public constructor(nodeElement: HTMLElement)
    {
        super(nodeElement);
        this._behindNodes = [];
        this._animationStartTime = 0;
        this._curveAppearProgress = [0, 0, 0, 0];
        this._appearStatus = AppearStatus.DISAPPEARED;
        this._appearAnimationFunc = null;
        this._maxEndOpacity = 0.3;
        this._minEndOpacity = 0.1;
    }

    public loadNodes(): void
    {
        this._behindNodes = Array.from(this._contentElement.querySelectorAll(':scope > .behind-node'))
            .map(node => new BehindNode(node as HTMLElement));
    }

    public update(): void
    {
        if (this._appearAnimationFunc !== null) {
            this._appearAnimationFunc();
        }
    }

    public appear(): void
    {
        this._animationStartTime = (window as any).hgn.timestamp;
        this._curveAppearProgress = [0, 0, 0, 0];
        this._appearStatus = AppearStatus.APPEARING;
        this._appearAnimationFunc = this.appearAnimation;
    }
    
    /**
     * 出現アニメーション
     */
    protected appearAnimation(): void
    {
        const progress = Util.getAnimationProgress(this._animationStartTime, 1000);
        if (progress >= 1) {
            this._curveAppearProgress = [1, 1, 1, 1];
            this._appearStatus = AppearStatus.APPEARED;
            this._appearAnimationFunc = null;
        }

        this._curveAppearProgress[0] = progress * 2;
        if (this._curveAppearProgress[0] > 1) {
            this._curveAppearProgress[0] = 1;

            if (this._behindNodes.length > 0) {
                this._behindNodes[0].visible();
            }
        }
        
        this._curveAppearProgress[1] = progress * 1.5;
        if (this._curveAppearProgress[1] > 1) {
            this._curveAppearProgress[1] = 1;
            if (this._behindNodes.length > 1) {
                this._behindNodes[1].visible();
            }
        }
        this._curveAppearProgress[2] = progress * 1.2;
        if (this._curveAppearProgress[2] > 1) {
            this._curveAppearProgress[2] = 1;
            if (this._behindNodes.length > 2) {
                this._behindNodes[2].visible();
            }
        }

        this._curveAppearProgress[3] = progress;
        if (this._curveAppearProgress[3] >= 1) {
            this._curveAppearProgress[3] = 1;
            if (this._behindNodes.length > 3) {
                this._behindNodes[3].visible();
            }
        }
    }

    public disappear(): void
    {
        this._curveAppearProgress = [0, 0, 0, 0];

        this._behindNodes.forEach(behindNode => behindNode.invisible());

        this._appearStatus = AppearStatus.DISAPPEARED;
        this._appearAnimationFunc = null;
    }

    public draw(renderer: CurveRenderer, connectionPoint: Point): void
    {
        if (this._curveAppearProgress[0] > 0) {
            const containerRect = renderer.getContainerRect();
            this._behindNodes.forEach((behindNode, index) => {
                if (index >= 4) return; // 4つ以上は描画しない
                const screen = behindNode.getConnectionPoint();
                const endPoint = new Point(
                    screen.x - containerRect.left,
                    screen.y - containerRect.top
                );
                renderer.drawBehindCurve(
                    connectionPoint,
                    endPoint,
                    index,
                    this._curveAppearProgress[index]
                );
            });
        }
    }


    public invisible(): void
    {
        this._behindNodes.forEach(behindNode => behindNode.invisible());
    }

    public visible(): void
    {
        this._behindNodes.forEach(behindNode => behindNode.visible());
    }

    public hover(): void
    {
        this._contentElement.classList.add('hover');
    }

    public unhover(): void
    {
        this._contentElement.classList.remove('hover');
    }
}