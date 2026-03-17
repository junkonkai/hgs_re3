/**
 * AnimationScheduler に登録できる対象の共通契約。
 * update が false を返したら登録解除される。
 */
export interface Animatable
{
    /**
     * @param timestamp 現在時刻（performance.now() または requestAnimationFrame のコールバック引数）
     * @returns true: 次フレームも継続 / false: 完了したので登録解除可能
     */
    update(timestamp: number): boolean;
}
