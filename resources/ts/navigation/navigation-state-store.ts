import type { NavigationRequest } from "./types";
import type { NavigationResult } from "./types";

/**
 * 遷移中の一時状態を保持する。
 * 直近の NavigationRequest / NavigationResult と遷移中フラグを持つ。
 */
export class NavigationStateStore
{
    private _request: NavigationRequest | null = null;
    private _result: NavigationResult | null = null;
    private _navigating: boolean = false;

    public get isNavigating(): boolean
    {
        return this._navigating;
    }

    public get request(): NavigationRequest | null
    {
        return this._request;
    }

    public get result(): NavigationResult | null
    {
        return this._result;
    }

    public start(request: NavigationRequest): void
    {
        this._request = request;
        this._result = null;
        this._navigating = true;
    }

    public resolve(result: NavigationResult): void
    {
        this._result = result;
        this._navigating = false;
    }

    public clear(): void
    {
        this._request = null;
        this._result = null;
        this._navigating = false;
    }
}
