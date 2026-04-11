import { Component } from "./component";
import { FearMeterCommentReaction } from "./components/fear_meter_comment_reaction";
import { FearMeterFormInput } from "./components/fear_meter_form_input";
import { LineupSearch } from "./components/lineup_search";
import { ReviewFormInput } from "./components/review_form_input";
import { ReviewReaction } from "./components/review_reaction";
import { TitleDetailFavorite } from "./components/title_detail_favorite";
import { OtpInput } from "./components/otp_input";

/**
 * コンポーネント管理クラス
 */
export class ComponentManager
{
    private static _instance: ComponentManager;
    private _components: Component[] = [];
    private _componentMap: { [key: string]: new (...args: any[]) => Component } = {
        'LineupSearch': LineupSearch,
        'TitleDetailFavorite': TitleDetailFavorite,
        'FearMeterCommentReaction': FearMeterCommentReaction,
        'FearMeterFormInput': FearMeterFormInput,
        'ReviewFormInput': ReviewFormInput,
        'ReviewReaction': ReviewReaction,
        'OtpInput': OtpInput,
    };

    /**
     * インスタンスを返す
     */
    public static getInstance(): ComponentManager
    {
        if (!ComponentManager._instance) {
            ComponentManager._instance = new ComponentManager();
        }
        return ComponentManager._instance;
    }

    /**
     * コンストラクタ
     */
    private constructor()
    {
    }

    /**
     * コンポーネントを初期化する
     * @param components コンポーネント名と初期化パラメーターのオブジェクト
     */
    public initializeComponents(components: { [componentName: string]: any | null }): void
    {
        Object.keys(components).forEach(componentName => {
            const ComponentClass = this._componentMap[componentName];
            if (ComponentClass) {
                const params = components[componentName];
                const instance = new ComponentClass(params);
                this._components.push(instance);
            }
        });
    }

    /**
     * コンポーネントを破棄する
     */
    public disposeComponents(): void
    {
        this._components.forEach(component => {
            component.dispose();
        });
        this._components = [];
    }
}
