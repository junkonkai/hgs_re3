import { Point } from "../../../common/point";

/**
 * 接続線描画の共通契約。
 * ノード側は「線の描画方法」を知らず、開始点・終了点・進行率・グラデーションのみ渡す。
 */
export interface CurveRenderer
{
    resize(): void;
    clear(): void;
    setPath(startPoint: Point, endPoint: Point): void;
    setProgress(progress: number): void;
    getProgress(): number;
    setGradient(startAlpha: number, endAlpha: number): void;
    drawBehindCurve(
        startPoint: Point,
        endPoint: Point,
        index: number,
        progress: number
    ): void;
    getContainerRect(): DOMRect;
    show(): void;
    hide(): void;
    dispose(): void;
}
