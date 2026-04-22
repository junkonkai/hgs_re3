import { Component } from "../component";

/**
 * 怖さメーター入力フォーム（- / + とバー表示連動）
 */
export class FearMeterFormInput extends Component
{
    private _root: HTMLElement | null = null;
    private _hiddenInput: HTMLInputElement | null = null;
    private _decreaseButton: HTMLButtonElement | null = null;
    private _increaseButton: HTMLButtonElement | null = null;
    private _rangeInput: HTMLInputElement | null = null;
    private _textLabel: HTMLElement | null = null;
    private _scoreLabel: HTMLElement | null = null;
    private _min: number = 0;
    private _max: number = 4;
    private _texts: Record<string, string> = {};
    private _onDecrease: (() => void) | null = null;
    private _onIncrease: (() => void) | null = null;
    private _onRangeInput: (() => void) | null = null;

    constructor(params: any | null = null)
    {
        super(params);

        this._root = document.querySelector('.js-fear-meter-input') as HTMLElement | null;
        if (!this._root) {
            return;
        }

        const form = this._root.closest('form');
        this._hiddenInput = form?.querySelector('.js-fear-meter-value') as HTMLInputElement | null;
        this._decreaseButton = this._root.querySelector('.js-fear-meter-decrease') as HTMLButtonElement | null;
        this._increaseButton = this._root.querySelector('.js-fear-meter-increase') as HTMLButtonElement | null;
        this._rangeInput = this._root.querySelector('.js-fear-meter-range') as HTMLInputElement | null;
        this._textLabel = this._root.querySelector('.js-fear-meter-text') as HTMLElement | null;
        this._scoreLabel = this._root.querySelector('.js-fear-meter-score') as HTMLElement | null;

        if (!this._hiddenInput || !this._decreaseButton || !this._increaseButton || !this._rangeInput || !this._textLabel || !this._scoreLabel) {
            return;
        }

        const minFromDataset = Number(this._root.dataset.fearMeterMin);
        const maxFromDataset = Number(this._root.dataset.fearMeterMax);
        this._min = Number.isFinite(minFromDataset) ? minFromDataset : 0;
        this._max = Number.isFinite(maxFromDataset) ? maxFromDataset : 4;
        if (this._max < this._min) {
            const tmp = this._min;
            this._min = this._max;
            this._max = tmp;
        }

        this._texts = this.parseTexts(this._root.dataset.fearMeterTexts || '{}');

        this._onDecrease = () => {
            this.render(Number(this._hiddenInput?.value) - 1);
        };
        this._onIncrease = () => {
            this.render(Number(this._hiddenInput?.value) + 1);
        };
        this._onRangeInput = () => {
            this.render(Number(this._rangeInput?.value));
        };

        this._decreaseButton.addEventListener('click', this._onDecrease);
        this._increaseButton.addEventListener('click', this._onIncrease);
        this._rangeInput.addEventListener('input', this._onRangeInput);

        this.render(Number(this._hiddenInput.value));
    }

    public dispose(): void
    {
        if (this._decreaseButton && this._onDecrease) {
            this._decreaseButton.removeEventListener('click', this._onDecrease);
        }
        if (this._increaseButton && this._onIncrease) {
            this._increaseButton.removeEventListener('click', this._onIncrease);
        }
        if (this._rangeInput && this._onRangeInput) {
            this._rangeInput.removeEventListener('input', this._onRangeInput);
        }
        this._onDecrease = null;
        this._onIncrease = null;
        this._onRangeInput = null;
    }

    private parseTexts(raw: string): Record<string, string>
    {
        try {
            const parsed = JSON.parse(raw) as unknown;
            if (!parsed || typeof parsed !== 'object') {
                return {};
            }
            return parsed as Record<string, string>;
        } catch (_error) {
            return {};
        }
    }

    private render(rawValue: number): void
    {
        if (!this._hiddenInput || !this._decreaseButton || !this._increaseButton || !this._rangeInput || !this._textLabel || !this._scoreLabel) {
            return;
        }

        const clamped = Math.min(this._max, Math.max(this._min, Math.round(rawValue)));

        this._hiddenInput.value = String(clamped);
        this._hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        this._rangeInput.value = String(clamped);
        this._textLabel.textContent = this._texts[String(clamped)] ?? '';
        this._scoreLabel.textContent = `(+${clamped * 10})`;
        this._decreaseButton.disabled = clamped <= this._min;
        this._increaseButton.disabled = clamped >= this._max;
    }
}
