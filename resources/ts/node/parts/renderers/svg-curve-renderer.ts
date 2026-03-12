import { Config } from "../../../common/config";
import { NodeBase } from "../../node-base";
import { Point } from "../../../common/point";
import { CurveRenderer } from "./curve-renderer";

let _svgCurveIdCounter = 0;

/**
 * SVG + stroke-dashoffset で接続線を描画。色は CSS 変数直接参照で getComputedStyle を呼ばない。
 */
export class SvgCurveRenderer implements CurveRenderer
{
    private _svg: SVGSVGElement;
    private _curvePath: SVGPathElement;
    private _behindPaths: SVGPathElement[];
    private _parentNode: NodeBase;
    private _gradientId: string;
    private _gradientElement: SVGLinearGradientElement;
    private _stopStart: SVGStopElement;
    private _stopEnd: SVGStopElement;

    private _progress: number;
    private _gradientStartAlpha: number;
    private _gradientEndAlpha: number;
    private _pathD: string;
    private _totalLength: number;
    private _containerRect: DOMRect;
    private _lastStartPoint: Point | null;
    private _lastEndPoint: Point | null;

    public constructor(parentNode: NodeBase)
    {
        this._parentNode = parentNode;
        this._progress = 0;
        this._gradientStartAlpha = 1;
        this._gradientEndAlpha = 0;
        this._pathD = '';
        this._totalLength = 0;
        this._containerRect = new DOMRect(0, 0, 0, 0);
        this._lastStartPoint = null;
        this._lastEndPoint = null;

        const id = (parentNode.nodeElement.id || `svg-curve-${++_svgCurveIdCounter}`).replace(/\s/g, '-');
        this._gradientId = `curveGrad-${id}`;

        this._svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this._svg.setAttribute('class', 'node-curve-svg');

        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        this._gradientElement = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
        this._gradientElement.id = this._gradientId;
        this._gradientElement.setAttribute('gradientUnits', 'userSpaceOnUse');
        this._stopStart = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        this._stopStart.setAttribute('offset', '0');
        this._stopStart.setAttribute('stop-color', 'var(--node-pt-light)');
        this._stopEnd = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        this._stopEnd.setAttribute('offset', '1');
        this._stopEnd.setAttribute('stop-color', 'var(--node-pt-light)');
        this._gradientElement.appendChild(this._stopStart);
        this._gradientElement.appendChild(this._stopEnd);
        defs.appendChild(this._gradientElement);
        this._svg.appendChild(defs);

        this._curvePath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        this._curvePath.setAttribute('class', 'curve-path');
        this._curvePath.setAttribute('fill', 'none');
        this._curvePath.setAttribute('stroke', `url(#${this._gradientId})`);
        this._curvePath.setAttribute('stroke-width', '2');
        this._curvePath.setAttribute('stroke-linecap', 'round');
        this._svg.appendChild(this._curvePath);

        this._behindPaths = [];
        for (let i = 0; i < 4; i++) {
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('class', `behind-path behind-path-${i}`);
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', 'var(--node-pt-light)');
            path.setAttribute('stroke-width', '2');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('visibility', 'hidden');
            this._behindPaths.push(path);
            this._svg.appendChild(path);
        }

        this._parentNode.nodeElement.appendChild(this._svg);
        this.resize();
        // 最初は非表示。startAppear() で show() して connectionLine と同時に登場させる
        this.hide();
    }

    public resize(): void
    {
        const el = this._parentNode.nodeElement;
        const w = Math.max(1, el.offsetWidth);
        const nodeRect = el.getBoundingClientRect();
        let h = Math.max(1, el.offsetHeight);

        const behindContent = el.querySelector('.node-content.behind');
        if (behindContent) {
            const behindNodes = behindContent.querySelectorAll('.behind-node');
            let maxBottom = nodeRect.bottom;
            behindNodes.forEach((bn) => {
                const r = bn.getBoundingClientRect();
                if (r.bottom > maxBottom) maxBottom = r.bottom;
            });
            h = Math.max(h, Math.round(maxBottom - nodeRect.top));
        }

        this._svg.setAttribute('width', String(w));
        this._svg.setAttribute('height', String(h));
        this._svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
        this._svg.style.width = w + 'px';
        this._svg.style.height = h + 'px';
        this._containerRect = this._svg.getBoundingClientRect();
    }

    public getContainerRect(): DOMRect
    {
        return this._containerRect;
    }

    public clear(): void
    {
        this._curvePath.setAttribute('d', '');
        this._curvePath.style.strokeDasharray = '';
        this._curvePath.style.strokeDashoffset = '';
        this._behindPaths.forEach(p => {
            p.setAttribute('d', '');
            p.style.strokeDasharray = '';
            p.style.strokeDashoffset = '';
            p.setAttribute('visibility', 'hidden');
        });
    }

    public setPath(startPoint: Point, endPoint: Point): void
    {
        const sx = startPoint.x;
        const sy = startPoint.y;
        const ex = endPoint.x;
        const ey = endPoint.y;
        this._lastStartPoint = startPoint;
        this._lastEndPoint = endPoint;
        this._pathD = `M${sx},${sy} Q${sx},${ey} ${ex},${ey}`;
        this._curvePath.setAttribute('d', this._pathD);
        this._totalLength = this._curvePath.getTotalLength();
        this.applyProgress();
    }

    /** 進行度に応じてグラデーション終了点を補間（Canvas 互換） */
    private updateGradientEndPoint(): void
    {
        const s = this._lastStartPoint;
        const e = this._lastEndPoint;
        if (!s || !e) return;
        let x2 = e.x;
        let y2 = e.y;
        if (this._progress < 1) {
            x2 = s.x + (e.x - s.x) * this._progress;
            y2 = s.y + (e.y - s.y) * this._progress;
        }
        this._gradientElement.setAttribute('x1', String(s.x));
        this._gradientElement.setAttribute('y1', String(s.y));
        this._gradientElement.setAttribute('x2', String(x2));
        this._gradientElement.setAttribute('y2', String(y2));
    }

    private applyProgress(): void
    {
        this.updateGradientEndPoint();
        if (this._totalLength > 0) {
            this._curvePath.style.strokeDasharray = String(this._totalLength);
            this._curvePath.style.strokeDashoffset = String(this._totalLength * (1 - this._progress));
        }
        this._stopStart.setAttribute('stop-opacity', String(this._gradientStartAlpha));
        this._stopEnd.setAttribute('stop-opacity', String(this._gradientEndAlpha));
    }

    public setProgress(progress: number): void
    {
        if (progress < 0) progress = 0;
        if (progress > 1) progress = 1;
        this._progress = progress;
        this.applyProgress();
    }

    public getProgress(): number
    {
        return this._progress;
    }

    public setGradient(startAlpha: number, endAlpha: number): void
    {
        this._gradientStartAlpha = startAlpha;
        this._gradientEndAlpha = endAlpha;
        this.applyProgress();
    }

    public drawBehindCurve(
        startPoint: Point,
        endPoint: Point,
        index: number,
        progress: number
    ): void
    {
        if (index < 0 || index >= 4) return;

        const path = this._behindPaths[index];
        const sx = startPoint.x;
        const sy = startPoint.y;
        const ex = endPoint.x;
        const ey = endPoint.y;
        const cx = sx + (ex - sx) * 0.1;
        const d = `M${sx},${sy} Q${cx},${ey} ${ex},${ey}`;
        path.setAttribute('d', d);

        const len = path.getTotalLength();
        path.style.strokeDasharray = String(len);
        path.style.strokeDashoffset = String(len * (1 - progress));

        const config = Config.getInstance();
        const opacity = config.BEHIND_CURVE_LINE_MAX_OPACITY - (index * 0.1);
        let endOpacity = Math.max(config.BEHIND_CURVE_LINE_MIN_OPACITY, opacity - config.BEHIND_CURVE_LINE_MIN_OPACITY);
        if (progress < 1) endOpacity = endOpacity * progress;
        path.style.strokeOpacity = String(endOpacity);
        path.setAttribute('stroke', 'var(--node-pt-light)');
        path.style.visibility = progress > 0 ? 'visible' : 'hidden';
    }

    public show(): void
    {
        this._svg.style.visibility = 'visible';
    }

    public hide(): void
    {
        this._svg.style.visibility = 'hidden';
    }

    public dispose(): void
    {
        this._svg.remove();
    }
}
