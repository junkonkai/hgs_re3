/**
 * 進行率に対するイージング関数。
 * Phase3 では linear と easeOutCubic を利用。
 */
export class Easing
{
    public static linear(t: number): number
    {
        return t;
    }

    public static easeOutCubic(t: number): number
    {
        return 1 - Math.pow(1 - t, 3);
    }

    public static easeInOutCubic(t: number): number
    {
        return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }
}
