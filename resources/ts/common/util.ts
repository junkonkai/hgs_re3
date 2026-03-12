import { Point } from "./point";
import { ClickableNodeInterface } from "../node/interface/clickable-node-interface";

export class Util
{
    /**
     * URLにGETパラメーターa=1を付与する
     * @param url 元のURL文字列
     * @returns パラメーターが付与されたURL
     */
    public static addParameterA(url: string): string
    {
        try {
            const urlObj = new URL(url);
            urlObj.searchParams.set('a', '1');
            return urlObj.toString();
        } catch (error) {
            // URLが無効な場合は、元のURLに?または&を付けてa=1を追加
            const separator = url.includes('?') ? '&' : '?';
            return `${url}${separator}a=1`;
        }
    }

    /**
     * アニメーションの値を計算
     * @param startValue 開始値
     * @param endValue 終了値
     * @param startTime アニメーションの開始時間
     * @param duration アニメーションの持続時間（ミリ秒）
     * @returns 現在の値
     */
    public static getAnimationValue(startValue: number, endValue: number, startTime: number, duration: number): number
    {
        const progress = Util.getAnimationProgress(startTime, duration);
        return startValue + (endValue - startValue) * progress;
    }

    /**
     * アニメーションの進行度を計算（0.0～1.0）
     * @param startTime アニメーションの開始時間
     * @param duration
     * @returns 進行度（0.0～1.0）。currentTime は (window as any).hgn.timestamp を使用（後方互換）
     */
    public static getAnimationProgress(startTime: number, duration: number): number
    {
        const currentTime = (window as any).hgn?.timestamp ?? performance.now();
        return Util.getLinearProgress(currentTime, startTime, duration);
    }

    /**
     * Phase3: 線形進行度（0.0～1.0）。timestamp を引数で渡すことで hgn 依存を避けられる。
     */
    public static getLinearProgress(currentTime: number, startTime: number, duration: number): number
    {
        const elapsedTime = currentTime - startTime;
        if (elapsedTime <= 0) {
            return 0;
        }
        if (elapsedTime >= duration) {
            return 1.0;
        }
        return elapsedTime / duration;
    }

    /**
     * 二次ベジェ曲線上の座標を計算する
     * @param startX 開始点のX座標
     * @param startY 開始点のY座標
     * @param endX 終了点のX座標
     * @param endY 終了点のY座標
     * @param t 進行度（0.0～1.0）
     * @returns 指定された進行度での座標
     */
    public static getQuadraticBezierPoint(
        startX: number, 
        startY: number, 
        endX: number, 
        endY: number, 
        t: number
        ): Point
    {
        const controlX = startX;
        const controlY = endY;
        // 二次ベジェ曲線の数式: B(t) = (1-t)²P₀ + 2(1-t)tP₁ + t²P₂
        const x = Math.floor(Math.pow(1 - t, 2) * startX + 2 * (1 - t) * t * controlX + Math.pow(t, 2) * endX);
        const y = Math.floor(Math.pow(1 - t, 2) * startY + 2 * (1 - t) * t * controlY + Math.pow(t, 2) * endY);
        
        return new Point(x, y);
    }
    

    /**
     * 現在のスタックトレースをコンソールに出力する
     */
    public static logStackTrace(): void
    {
        try {
            // Errorオブジェクトを使用してスタックトレースを取得
            const error = new Error();
            if (error.stack) {
                console.log('=== スタックトレース ===');
                console.log(error.stack);
                console.log('=====================');
            } else {
                // スタックトレースが取得できない場合の代替手段
                console.log('=== スタックトレース（代替） ===');
                console.trace('現在の呼び出しスタック');
                console.log('============================');
            }
        } catch (e) {
            console.log('スタックトレースの取得に失敗しました:', e);
        }
    }

    /**
     * クリック可能なノードかどうかを判定する型ガード
     * @param node チェック対象のオブジェクト
     * @returns ClickableNodeInterfaceを実装しているかどうか
     */
    public static isClickableNode(node: any): node is ClickableNodeInterface
    {
        return typeof node.click === 'function' && 
               typeof node.hover === 'function' && 
               typeof node.unhover === 'function';
    }

    /**
     * CSS変数から色を取得する
     * @param variableName CSS変数名
     * @returns RGB値の配列 [r, g, b]
     */
    public static getColorFromCSSVariable(variableName: string): [number, number, number]
    {
        // body要素から取得（body.has-errorで定義されている変数を優先的に取得）
        const colorValue = getComputedStyle(document.body)
            .getPropertyValue(variableName)
            .trim();
        
        // #66ff66 形式を [102, 255, 102] に変換
        if (colorValue.startsWith('#')) {
            const hex = colorValue.slice(1);
            const r = parseInt(hex.slice(0, 2), 16);
            const g = parseInt(hex.slice(2, 4), 16);
            const b = parseInt(hex.slice(4, 6), 16);
            return [r, g, b];
        }
        
        // フォールバック（デフォルトの緑色）
        return [144, 255, 144];
    }
} 