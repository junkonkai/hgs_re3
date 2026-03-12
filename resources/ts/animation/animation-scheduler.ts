import type { Animatable } from "./animatable";

/**
 * rAF の開始・停止を管理し、登録された Animatable だけを更新する。
 * 静止時はループを止める。
 */
export class AnimationScheduler
{
    private _animatables: Set<Animatable> = new Set();
    private _isRunning: boolean = false;
    private _timestamp: number = 0;
    private _rafId: number | null = null;

    public get timestamp(): number
    {
        return this._timestamp;
    }

    public get isRunning(): boolean
    {
        return this._isRunning;
    }

    public register(animatable: Animatable): void
    {
        this._animatables.add(animatable);
    }

    public unregister(animatable: Animatable): void
    {
        this._animatables.delete(animatable);
    }

    /**
     * 次のフレームを要求する。未起動なら rAF を開始する。
     */
    public requestTick(): void
    {
        if (this._animatables.size === 0) {
            return;
        }
        if (!this._isRunning) {
            this._isRunning = true;
            this._rafId = requestAnimationFrame((ts) => this._tick(ts));
        }
    }

    private _tick(timestamp: number): void
    {
        this._timestamp = timestamp;
        this._rafId = null;

        const toRemove: Animatable[] = [];
        for (const a of this._animatables) {
            try {
                if (!a.update(timestamp)) {
                    toRemove.push(a);
                }
            } catch (e) {
                console.error('AnimationScheduler: animatable.update failed', e);
                toRemove.push(a);
            }
        }
        toRemove.forEach(a => this._animatables.delete(a));

        if (this._animatables.size > 0) {
            this._rafId = requestAnimationFrame((ts) => this._tick(ts));
        } else {
            this._isRunning = false;
        }
    }
}
