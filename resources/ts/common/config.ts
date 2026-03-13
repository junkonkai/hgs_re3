/**
 * アプリケーション全体の設定を管理するクラス
 */
export class Config
{
    // シングルトンインスタンス
    private static instance: Config | null = null;

    // 設定プロパティ（定数）
    public readonly BEHIND_CURVE_LINE_MAX_OPACITY: number;
    public readonly BEHIND_CURVE_LINE_MIN_OPACITY: number;
    public readonly CONNECTION_LINE_TYPE_THRESHOLD: number;
    public readonly CONNECTION_LINE_SHORT_APPEAR_SPEED: number;
    public readonly CONNECTION_LINE_LONG_APPEAR_TIME: number;
    public readonly CONNECTION_LINE_FAST_SHORT_APPEAR_SPEED: number;
    public readonly CONNECTION_LINE_FAST_LONG_APPEAR_TIME: number;
    public readonly CONNECTION_LINE_SHORT_DISAPPEAR_SPEED: number;
    public readonly CONNECTION_LINE_LONG_DISAPPEAR_TIME: number;
    public readonly CONNECTION_LINE_FAST_SHORT_DISAPPEAR_SPEED: number;
    public readonly CONNECTION_LINE_FAST_LONG_DISAPPEAR_TIME: number;
    /** Phase4: true のとき接続線に SvgCurveRenderer を使用 */
    public readonly USE_SVG_CURVE: boolean;
    /** カーブの出現・消失アニメーション時間（ミリ秒） */
    public readonly CURVE_ANIMATION_DURATION: number;

    /**
     * プライベートコンストラクタ（シングルトンパターン）
     */
    private constructor()
    {
        // デフォルト設定値
        this.USE_SVG_CURVE = true;
        this.CURVE_ANIMATION_DURATION = 133;
        this.BEHIND_CURVE_LINE_MAX_OPACITY = 0.3;
        this.BEHIND_CURVE_LINE_MIN_OPACITY = 0.1;
        this.CONNECTION_LINE_TYPE_THRESHOLD = 1000;
        this.CONNECTION_LINE_SHORT_APPEAR_SPEED = 20;
        this.CONNECTION_LINE_LONG_APPEAR_TIME = 1000;
        this.CONNECTION_LINE_FAST_SHORT_APPEAR_SPEED = 35;
        this.CONNECTION_LINE_FAST_LONG_APPEAR_TIME = 700;
        this.CONNECTION_LINE_SHORT_DISAPPEAR_SPEED = 26;
        this.CONNECTION_LINE_LONG_DISAPPEAR_TIME = 500;
        this.CONNECTION_LINE_FAST_SHORT_DISAPPEAR_SPEED = 25;
        this.CONNECTION_LINE_FAST_LONG_DISAPPEAR_TIME = 300;
    }

    /**
     * シングルトンインスタンスを取得
     * @returns Configインスタンス
     */
    public static getInstance(): Config
    {
        if (Config.instance === null) {
            Config.instance = new Config();
        }
        return Config.instance;
    }

    /**
     * 設定を更新する
     * @param config 更新する設定オブジェクト
     */
    public updateConfig(config: Partial<Config>): void
    {
        Object.assign(this, config);
    }

    /**
     * 設定をリセットする
     */
    public resetConfig(): void
    {
        Config.instance = null;
        Config.instance = new Config();
    }

    /**
     * 現在の設定を取得する
     * @returns 設定オブジェクト
     */
    public getConfig(): Partial<Config>
    {
        return {
            BEHIND_CURVE_LINE_MAX_OPACITY: this.BEHIND_CURVE_LINE_MAX_OPACITY,
            BEHIND_CURVE_LINE_MIN_OPACITY: this.BEHIND_CURVE_LINE_MIN_OPACITY,
            CONNECTION_LINE_TYPE_THRESHOLD: this.CONNECTION_LINE_TYPE_THRESHOLD,
            CONNECTION_LINE_SHORT_APPEAR_SPEED: this.CONNECTION_LINE_SHORT_APPEAR_SPEED,
            CONNECTION_LINE_LONG_APPEAR_TIME: this.CONNECTION_LINE_LONG_APPEAR_TIME,
            CONNECTION_LINE_FAST_SHORT_APPEAR_SPEED: this.CONNECTION_LINE_FAST_SHORT_APPEAR_SPEED,
            CONNECTION_LINE_FAST_LONG_APPEAR_TIME: this.CONNECTION_LINE_FAST_LONG_APPEAR_TIME,
            CONNECTION_LINE_SHORT_DISAPPEAR_SPEED: this.CONNECTION_LINE_SHORT_DISAPPEAR_SPEED,
            CONNECTION_LINE_LONG_DISAPPEAR_TIME: this.CONNECTION_LINE_LONG_DISAPPEAR_TIME,
            CONNECTION_LINE_FAST_SHORT_DISAPPEAR_SPEED: this.CONNECTION_LINE_FAST_SHORT_DISAPPEAR_SPEED,
            CONNECTION_LINE_FAST_LONG_DISAPPEAR_TIME: this.CONNECTION_LINE_FAST_LONG_DISAPPEAR_TIME,
            USE_SVG_CURVE: this.USE_SVG_CURVE,
            CURVE_ANIMATION_DURATION: this.CURVE_ANIMATION_DURATION
        };
    }
}
