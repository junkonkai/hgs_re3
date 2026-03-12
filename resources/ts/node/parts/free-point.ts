import { Util } from "../../common/util";
import { HgnTree } from "../../hgn-tree";

/**
 * Phase3: 表示位置は transform: translate3d() で制御し、left/top の reflow を避ける。
 */
export class FreePoint
{
    private _element: HTMLDivElement;
    public pos: { x: number; y: number };

    /** Phase3: 基準位置からのオフセット（moveOffset で加算） */
    private _offset: { x: number; y: number };
    private _animationFunc: (() => void) | null;
    private _animationGoalPos: { x: number; y: number };
    private _animationStartTime: number;
    private _halfWidth: number;
    private _halfHeight: number;

    public get element(): HTMLDivElement
    {
        return this._element;
    }

    /**
     * コンストラクタ
     */
    public constructor(parentNodeElement: HTMLElement)
    {
        this._element = document.createElement('div');
        this._element.textContent = '●';
        this._element.classList.add('free-pt');
        parentNodeElement.appendChild(this._element);
        this.pos = { x: 0, y: 0 };
        this._offset = { x: 0, y: 0 };
        this._animationGoalPos = { x: 0, y: 0 };
        this._animationFunc = null;
        this._animationStartTime = 0;
        this._halfWidth = this._element.clientWidth / 2;
        this._halfHeight = this._element.clientHeight / 2;
    }

    public get clientWidth(): number
    {
        return this._element.clientWidth;
    }

    public get clientHeight(): number
    {
        return this._element.clientHeight;
    }

    public setPos(x: number, y: number): FreePoint
    {
        this.pos.x = x;
        this.pos.y = y;
        return this;
    }

    /** Phase3: オフセットのみ transform で反映（アニメーション中は reflow を起こさない） */
    private applyTransform(): void
    {
        this._element.style.transform = `translate3d(${this._offset.x}px, ${this._offset.y}px, 0)`;
    }

    public setElementPos(): FreePoint
    {
        this._offset.x = 0;
        this._offset.y = 0;
        this._element.style.left = `${this.pos.x - this._halfWidth}px`;
        this._element.style.top = `${this.pos.y - this._halfHeight}px`;
        this._element.style.transform = 'translate3d(0,0,0)';
        return this;
    }

    /** オフセットを基準位置に反映し、オフセットをリセットする。 */
    public fixOffset(): FreePoint
    {
        this.pos.x += this._offset.x;
        this.pos.y += this._offset.y;
        this._offset.x = 0;
        this._offset.y = 0;
        this._element.style.left = `${this.pos.x - this._halfWidth}px`;
        this._element.style.top = `${this.pos.y - this._halfHeight}px`;
        this._element.style.transform = 'translate3d(0,0,0)';
        return this;
    }

    public moveOffset(x: number, y: number): FreePoint
    {
        this._offset.x = x;
        this._offset.y = y;
        this.applyTransform();
        return this;
    }

    public show(): void
    {
        this._element.classList.add('visible');
    }

    public hide(): void
    {
        this._element.classList.remove('visible');
    }

    public update(): void
    {
        if (this._animationFunc !== null) {
            this._animationFunc();
        }
    }

    public moveTo(goalPos: { x: number; y: number }): void
    {
        this._animationStartTime = HgnTree.getInstance().timestamp;

        this._animationGoalPos = {
            x: goalPos.x - this._element.clientWidth / 2,
            y: goalPos.y - this._element.clientHeight / 2,
        };
        this._animationFunc = this.moveAnimation;
    }

    private moveAnimation(): void
    {
        const progress = Util.getAnimationProgress(this._animationStartTime, 300);
        if (progress >= 1) {
            this._animationFunc = null;
            this.setPos(this._animationGoalPos.x, this._animationGoalPos.y);
            this._offset.x = 0;
            this._offset.y = 0;
            this.setElementPos();
        } else {
            this._offset.x = (this._animationGoalPos.x - this.pos.x) * progress;
            this._offset.y = (this._animationGoalPos.y - this.pos.y) * progress;
            this.applyTransform();
        }
    }
} 