import { Config } from "../../../common/config";
import { NodeBase } from "../../node-base";
import { Point } from "../../../common/point";
import { Util } from "../../../common/util";
import { CurveRenderer } from "./curve-renderer";

/**
 * 既存 CurveCanvas の互換実装。CurveRenderer 契約に寄せ、色は初回のみ取得・shadowBlur は撤去。
 */
export class CanvasCurveRenderer implements CurveRenderer
{
    private _canvas: HTMLCanvasElement;
    private _ctx: CanvasRenderingContext2D;
    private _parentNode: NodeBase;

    private _gradientStartAlpha: number;
    private _gradientEndAlpha: number;
    private _progress: number;

    /** 色はコンストラクタで一度だけ取得（毎フレーム getComputedStyle を避ける） */
    private _colorLight: [number, number, number];
    private _colorDark: [number, number, number];

    /** resize() で更新し、getContainerRect() で返す */
    private _containerRect: DOMRect;

    public constructor(parentNode: NodeBase)
    {
        this._parentNode = parentNode;
        this._canvas = document.createElement('canvas');
        this._canvas.classList.add('node-canvas');
        this._parentNode.nodeElement.appendChild(this._canvas);

        this._ctx = this._canvas.getContext('2d') as CanvasRenderingContext2D;
        this._gradientStartAlpha = 1;
        this._gradientEndAlpha = 0;
        this._progress = 0;
        this._containerRect = new DOMRect(0, 0, 0, 0);

        this._colorLight = Util.getColorFromCSSVariable('--node-pt-light');
        this._colorDark = Util.getColorFromCSSVariable('--node-pt-dark');

        this._ctx.lineWidth = 2;
        this._ctx.lineCap = 'round';
        // Phase4: shadowBlur 撤去

        this.resize();
        // 最初は非表示。startAppear() で show() して connectionLine と同時に登場させる
        this.hide();
    }

    public resize(): void
    {
        const el = this._parentNode.nodeElement;
        const width = Math.max(1, el.offsetWidth);
        const nodeRect = el.getBoundingClientRect();
        let height = Math.max(1, el.offsetHeight);

        const behindContent = el.querySelector('.node-content.behind');
        if (behindContent) {
            const behindNodes = behindContent.querySelectorAll('.behind-node');
            let maxBottom = nodeRect.bottom;
            behindNodes.forEach((bn) => {
                const r = bn.getBoundingClientRect();
                if (r.bottom > maxBottom) maxBottom = r.bottom;
            });
            height = Math.max(height, Math.round(maxBottom - nodeRect.top));
        }

        this._canvas.width = width;
        this._canvas.height = height;
        this._canvas.style.width = width + 'px';
        this._canvas.style.height = height + 'px';
        this._containerRect = this._canvas.getBoundingClientRect();
    }

    public getContainerRect(): DOMRect
    {
        return this._containerRect;
    }

    public clear(): void
    {
        this._ctx.clearRect(0, 0, this._canvas.width, this._canvas.height);
    }

    public setPath(startPoint: Point, endPoint: Point): void
    {
        // パスは draw 時に使用。Canvas 実装では setProgress 内で描画するため、現在のパスを保持する必要がある。
        // 呼び出し順は clear -> setPath -> setProgress または setPath -> setProgress なので、
        // 最後に setPath で渡された点を保持し、setProgress で描画する。
        (this as any)._lastStartPoint = startPoint;
        (this as any)._lastEndPoint = endPoint;
    }

    public setProgress(progress: number): void
    {
        if (progress < 0) {
            progress = 0;
        } else if (progress > 1) {
            progress = 1;
        }
        this._progress = progress;

        const startPoint = (this as any)._lastStartPoint as Point | undefined;
        const endPoint = (this as any)._lastEndPoint as Point | undefined;
        if (startPoint && endPoint && this._progress > 0) {
            this.drawCurvedLineInternal(startPoint, endPoint);
        }
    }

    public getProgress(): number
    {
        return this._progress;
    }

    public setGradient(startAlpha: number, endAlpha: number): void
    {
        this._gradientStartAlpha = startAlpha;
        this._gradientEndAlpha = endAlpha;
    }

    private drawCurvedLineInternal(startPoint: Point, endPoint: Point): void
    {
        const controlX = startPoint.x;
        const controlY = endPoint.y;
        let currentEndX = endPoint.x;
        let currentEndY = endPoint.y;

        if (this._progress < 1) {
            currentEndX = startPoint.x + (endPoint.x - startPoint.x) * this._progress;
            currentEndY = startPoint.y + (endPoint.y - startPoint.y) * this._progress;
        }

        const [r, g, b] = this._colorLight;
        const gradient = this._ctx.createLinearGradient(startPoint.x, startPoint.y, currentEndX, currentEndY);
        gradient.addColorStop(0, `rgba(${r}, ${g}, ${b}, ${this._gradientStartAlpha})`);
        gradient.addColorStop(1, `rgba(${r}, ${g}, ${b}, ${this._gradientEndAlpha})`);

        this._ctx.strokeStyle = gradient;
        this._ctx.beginPath();
        this._ctx.moveTo(startPoint.x, startPoint.y);
        this._ctx.quadraticCurveTo(controlX, controlY, endPoint.x, endPoint.y);
        this._ctx.stroke();
    }

    public drawBehindCurve(
        startPoint: Point,
        endPoint: Point,
        index: number,
        progress: number
    ): void
    {
        const config = Config.getInstance();
        const opacity = config.BEHIND_CURVE_LINE_MAX_OPACITY - (index * 0.1);
        let endOpacity = Math.max(config.BEHIND_CURVE_LINE_MIN_OPACITY, opacity - config.BEHIND_CURVE_LINE_MIN_OPACITY);

        let currentEndX = endPoint.x;
        let currentEndY = endPoint.y;

        if (progress < 1) {
            currentEndX = startPoint.x + (endPoint.x - startPoint.x) * progress;
            currentEndY = startPoint.y + (endPoint.y - startPoint.y) * progress;
            endOpacity = endOpacity * progress;
        }

        const [startR, startG, startB] = this._colorLight;
        const [endR, endG, endB] = this._colorDark;
        const gradient = this._ctx.createLinearGradient(startPoint.x, startPoint.y, currentEndX, currentEndY);
        gradient.addColorStop(0, `rgba(${startR}, ${startG}, ${startB}, ${endOpacity})`);
        gradient.addColorStop(1, `rgba(${endR}, ${endG}, ${endB}, ${endOpacity})`);

        this._ctx.beginPath();
        this._ctx.strokeStyle = gradient;
        this._ctx.lineWidth = 2;
        this._ctx.moveTo(startPoint.x, startPoint.y);
        this._ctx.quadraticCurveTo(
            startPoint.x + (endPoint.x - startPoint.x) * 0.1,
            endPoint.y,
            endPoint.x,
            endPoint.y
        );
        this._ctx.stroke();
    }

    public show(): void
    {
        this._canvas.style.visibility = 'visible';
    }

    public hide(): void
    {
        this._canvas.style.visibility = 'hidden';
    }

    public dispose(): void
    {
        this._canvas.remove();
    }
}
