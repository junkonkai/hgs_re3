/**
 * Phase5: 単一ノード要素へ depth 見た目を適用する。
 * prefers-reduced-motion とモバイル時の blur 弱めを考慮する。
 */

const ENTER_DURATION_MS = 220;
const EXIT_DURATION_MS = 180;

const DEPTH_SCALE_FACTOR = 0.05;
const DEPTH_OPACITY_FACTOR = 0.15;
const DEPTH_BLUR_DESKTOP = 1.2;
const DEPTH_BLUR_MOBILE = 0.6;
const EXIT_TRANSLATE_Z_PX = -80;
const EXIT_SCALE = 0.92;
const EXIT_OPACITY = 0.4;
const EXIT_BLUR_PX = 2;

export class DepthEffectController
{
    private static _instance: DepthEffectController | null = null;
    private _prefersReducedMotion: boolean;
    private _isMobile: boolean;

    public static getInstance(): DepthEffectController
    {
        if (DepthEffectController._instance === null) {
            DepthEffectController._instance = new DepthEffectController();
        }
        return DepthEffectController._instance;
    }

    private constructor()
    {
        this._prefersReducedMotion = typeof window !== 'undefined' &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this._isMobile = typeof window !== 'undefined' &&
            window.matchMedia('(max-width: 768px)').matches;
    }

    private blurPx(depth: number): number
    {
        const factor = this._isMobile ? DEPTH_BLUR_MOBILE : DEPTH_BLUR_DESKTOP;
        return depth * factor;
    }

    /**
     * 常時 depth を適用（persistent 用）
     */
    public applyDepth(element: HTMLElement, depth: number): void
    {
        element.classList.add('node-depth-layer');
        const scale = Math.max(0.7, 1 - depth * DEPTH_SCALE_FACTOR);
        const opacity = Math.max(0.3, 1 - depth * DEPTH_OPACITY_FACTOR);
        const blur = this.blurPx(depth);

        if (this._prefersReducedMotion) {
            element.style.transform = `scale(${scale})`;
            element.style.opacity = String(opacity);
            element.style.filter = 'none';
            return;
        }
        element.style.transform = `translateZ(${depth * -20}px) scale(${scale})`;
        element.style.opacity = String(opacity);
        element.style.filter = blur > 0 ? `blur(${blur}px)` : 'none';
    }

    /**
     * depth スタイルとクラスを削除
     */
    public clearDepth(element: HTMLElement): void
    {
        element.classList.remove('node-depth-layer', 'node-depth-entering', 'node-depth-exiting');
        element.style.transform = '';
        element.style.opacity = '';
        element.style.filter = '';
    }

    /**
     * 奥から手前へ出現する演出。transition 完了で resolve する Promise を返す。
     */
    public playEnter(element: HTMLElement, depth: number): Promise<void>
    {
        return new Promise((resolve) => {
            element.classList.add('node-depth-layer', 'node-depth-entering');
            if (this._prefersReducedMotion) {
                element.style.transform = 'scale(0.95)';
                element.style.opacity = '0.5';
                element.style.filter = 'none';
            } else {
                element.style.transform = `translateZ(${EXIT_TRANSLATE_Z_PX}px) scale(${EXIT_SCALE})`;
                element.style.opacity = String(EXIT_OPACITY);
                element.style.filter = `blur(${EXIT_BLUR_PX}px)`;
            }

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    if (this._prefersReducedMotion) {
                        element.style.transform = 'scale(1)';
                        element.style.opacity = '1';
                    } else {
                        element.style.transform = 'translateZ(0) scale(1)';
                        element.style.opacity = '1';
                        element.style.filter = 'none';
                    }
                });
            });

            const onEnd = (): void => {
                element.removeEventListener('transitionend', onEnd);
                element.classList.remove('node-depth-entering');
                element.style.transform = '';
                element.style.opacity = '';
                element.style.filter = '';
                resolve();
            };
            element.addEventListener('transitionend', onEnd);
            setTimeout(() => onEnd(), ENTER_DURATION_MS + 50);
        });
    }

    /**
     * 手前から奥へ抜ける演出。transition 完了で resolve する Promise を返す。
     */
    public playExit(element: HTMLElement, depth: number): Promise<void>
    {
        return new Promise((resolve) => {
            element.classList.add('node-depth-layer', 'node-depth-exiting');
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    if (this._prefersReducedMotion) {
                        element.style.transform = 'scale(0.95)';
                        element.style.opacity = '0.5';
                        element.style.filter = 'none';
                    } else {
                        element.style.transform = `translateZ(${EXIT_TRANSLATE_Z_PX}px) scale(${EXIT_SCALE})`;
                        element.style.opacity = String(EXIT_OPACITY);
                        element.style.filter = `blur(${EXIT_BLUR_PX}px)`;
                    }
                });
            });

            const onEnd = (): void => {
                element.removeEventListener('transitionend', onEnd);
                element.classList.remove('node-depth-exiting');
                resolve();
            };
            element.addEventListener('transitionend', onEnd);
            setTimeout(() => onEnd(), EXIT_DURATION_MS + 50);
        });
    }

    /**
     * persistent 用: 手前寄りに補正（スタブ）
     */
    public setFocused(element: HTMLElement): void
    {
        this.clearDepth(element);
    }

    /**
     * 奥行き付き背景表現
     */
    public setBackground(element: HTMLElement, depth: number): void
    {
        this.applyDepth(element, depth);
    }
}
