import type { Animatable } from "./animatable";

export type NodeAnimationOptions = {
    isFast?: boolean;
    doNotAppearBehind?: boolean;
    easing?: 'linear' | 'easeOutCubic';
};

/** appear / disappear / hasActiveAnimation を持つノード相当 */
export interface NodeAnimatorTarget
{
    appear?(isFast?: boolean, doNotAppearBehind?: boolean): void;
    disappear?(isFast?: boolean, doNotAppearBehind?: boolean): void;
    disappearOnlyThisNode?(onComplete?: () => void): void;
    hasActiveAnimation?(): boolean;
}

/**
 * ノード単位の appear / disappear 進行管理。
 * Phase3 ではラッパーとして導入し、既存の appear/disappear をそのまま呼ぶ。
 * 将来、時間計算・easing をここに集約する。
 */
export class NodeAnimator implements Animatable
{
    private _target: NodeAnimatorTarget | null = null;
    private _resolve: (() => void) | null = null;

    public playAppear(target: NodeAnimatorTarget, options?: NodeAnimationOptions): Promise<void>
    {
        this._target = target;
        return new Promise<void>(resolve => {
            this._resolve = resolve;
            if (target.appear) {
                target.appear(options?.isFast ?? false, options?.doNotAppearBehind ?? false);
            }
            resolve();
        });
    }

    public playDisappear(target: NodeAnimatorTarget, options?: NodeAnimationOptions): Promise<void>
    {
        this._target = target;
        return new Promise<void>(resolve => {
            this._resolve = resolve;
            if (target.disappear) {
                target.disappear(options?.isFast ?? false, options?.doNotAppearBehind ?? false);
            }
            resolve();
        });
    }

    public playDisappearSolo(target: NodeAnimatorTarget, _options?: NodeAnimationOptions): Promise<void>
    {
        this._target = target;
        return new Promise<void>(resolve => {
            this._resolve = resolve;
            if (target.disappearOnlyThisNode) {
                target.disappearOnlyThisNode(() => resolve());
            } else {
                resolve();
            }
        });
    }

    /**
     * Animatable: 進行中なら true。Phase3 ではターゲットの hasActiveAnimation を返す。
     */
    public update(_timestamp: number): boolean
    {
        if (!this._target) {
            return false;
        }
        const hasActive = this._target.hasActiveAnimation?.() === true;
        if (!hasActive && this._resolve) {
            this._resolve();
            this._resolve = null;
            this._target = null;
        }
        return hasActive;
    }
}
