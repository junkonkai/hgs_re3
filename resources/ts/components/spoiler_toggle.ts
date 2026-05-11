import { Component } from "../component";

/**
 * ネタバレトグル
 *
 * iOS Safari では display:none → display:inline の CSS 切り替えでタッチヒットテストが
 * 更新されないため、CSS-only のチェックボックス方式ではなく JS で制御する。
 */
export class SpoilerToggle extends Component
{
    private _handlers: Map<HTMLButtonElement, () => void> = new Map();

    constructor(params: any | null = null)
    {
        super(params);

        const buttons = Array.from(document.querySelectorAll<HTMLButtonElement>('.js-spoiler-btn'));
        buttons.forEach((btn) => {
            const handler = () => this.toggle(btn);
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

    private toggle(btn: HTMLButtonElement): void
    {
        const container = btn.closest('[data-spoiler]');
        if (!container) {
            return;
        }
        const content = container.querySelector<HTMLElement>('.js-spoiler-content');
        if (!content) {
            return;
        }

        const isRevealed = container.getAttribute('data-spoiler') === 'revealed';

        if (isRevealed) {
            container.setAttribute('data-spoiler', 'hidden');
            content.style.opacity = '0.1';
            content.style.userSelect = 'none';
            btn.textContent = '表示する';
        } else {
            container.setAttribute('data-spoiler', 'revealed');
            content.style.opacity = '1';
            content.style.userSelect = 'auto';
            btn.textContent = '読みづらくする';
        }
    }
}
