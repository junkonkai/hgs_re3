import { Component } from "../component";

/**
 * ネタバレ表示
 *
 * iOS Safari の display:none → display:inline によるタッチヒットテスト問題を避けるため
 * CSS-only のチェックボックス方式ではなく JS で制御する。
 * 一度表示したら戻せない（片方向のみ）。
 */
export class SpoilerToggle extends Component
{
    private _handlers: Map<HTMLButtonElement, () => void> = new Map();

    constructor(params: any | null = null)
    {
        super(params);

        const buttons = Array.from(document.querySelectorAll<HTMLButtonElement>('.js-spoiler-btn'));
        buttons.forEach((btn) => {
            const handler = () => this.reveal(btn);
            btn.addEventListener('click', handler);
            this._handlers.set(btn, handler);
        });
    }

    public dispose(): void
    {
        this._handlers.forEach((handler, btn) => {
            btn.removeEventListener('click', handler);
        });
        this._handlers.clear();
    }

    private reveal(btn: HTMLButtonElement): void
    {
        const content = btn.parentElement?.querySelector<HTMLElement>('.js-spoiler-content');
        if (!content) {
            return;
        }

        content.style.opacity = '1';
        content.style.userSelect = 'auto';
        btn.remove();
    }
}
