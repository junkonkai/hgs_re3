import { Component } from "../component";

/**
 * レビュー入力フォーム（スコア計算・下書き保存ボタン制御）
 */
export class ReviewFormInput extends Component
{
    private _form: HTMLFormElement | null = null;
    private _draftSaveButton: HTMLButtonElement | null = null;
    private _totalScoreDisplay: HTMLElement | null = null;
    private _fearMeterInput: HTMLInputElement | null = null;
    private _scoreInputs: NodeListOf<HTMLInputElement> | null = null;
    private _adjustmentInput: HTMLInputElement | null = null;
    private _adjustmentValueLabel: HTMLElement | null = null;
    private _listeners: Array<{ el: EventTarget; type: string; fn: EventListener }> = [];

    constructor(params: any | null = null)
    {
        super(params);

        this._form = document.querySelector('.js-review-form') as HTMLFormElement | null;
        this._draftSaveButton = document.querySelector('.js-review-draft-save') as HTMLButtonElement | null;
        this._totalScoreDisplay = document.querySelector('.js-review-total-score') as HTMLElement | null;
        this._fearMeterInput = document.querySelector('.js-fear-meter-value') as HTMLInputElement | null;
        this._scoreInputs = document.querySelectorAll('.js-review-score-select') as NodeListOf<HTMLInputElement>;
        this._adjustmentInput = document.querySelector('.js-review-adjustment') as HTMLInputElement | null;
        this._adjustmentValueLabel = document.querySelector('.js-review-adjustment-value') as HTMLElement | null;

        this.updateTotalScore();

        const updateFn: EventListener = () => this.updateTotalScore();

        if (this._fearMeterInput) {
            this._fearMeterInput.addEventListener('input', updateFn);
            this._listeners.push({ el: this._fearMeterInput, type: 'input', fn: updateFn });
        }

        this._scoreInputs?.forEach((input, i) => {
            const valueLabel = this.getScoreValueLabel(input);
            const updateWithLabel: EventListener = () => {
                if (valueLabel) {
                    valueLabel.textContent = input.value;
                }
                this.updateTotalScore();
            };
            input.addEventListener('input', updateWithLabel);
            this._listeners.push({ el: input, type: 'input', fn: updateWithLabel });
        });

        if (this._adjustmentInput) {
            const adjustmentFn: EventListener = () => {
                if (this._adjustmentValueLabel && this._adjustmentInput) {
                    const val = Number(this._adjustmentInput.value);
                    this._adjustmentValueLabel.textContent = (val >= 0 ? '+' : '') + val;
                }
                this.updateTotalScore();
            };
            this._adjustmentInput.addEventListener('input', adjustmentFn);
            this._listeners.push({ el: this._adjustmentInput, type: 'input', fn: adjustmentFn });
        }

        if (this._draftSaveButton && this._form) {
            const draftUrl = this._draftSaveButton.dataset.draftUrl ?? '';
            const draftFn: EventListener = () => {
                if (this._form && draftUrl) {
                    this._form.action = draftUrl;
                    this._form.submit();
                }
            };
            this._draftSaveButton.addEventListener('click', draftFn);
            this._listeners.push({ el: this._draftSaveButton, type: 'click', fn: draftFn });
        }
    }

    private getScoreValueLabel(input: HTMLInputElement): HTMLElement | null
    {
        let sibling = input.nextElementSibling as HTMLElement | null;
        while (sibling) {
            if (sibling.classList.contains('js-review-score-value')) {
                return sibling;
            }
            sibling = sibling.nextElementSibling as HTMLElement | null;
        }
        return null;
    }

    public dispose(): void
    {
        this._listeners.forEach(({ el, type, fn }) => el.removeEventListener(type, fn));
        this._listeners = [];
    }

    private updateTotalScore(): void
    {
        if (!this._totalScoreDisplay) {
            return;
        }

        const fearMeter = this._fearMeterInput ? Number(this._fearMeterInput.value) : 0;
        let base = (Number.isFinite(fearMeter) ? fearMeter : 0) * 10;

        this._scoreInputs?.forEach(input => {
            if (input.value !== '') {
                const val = Number(input.value);
                if (Number.isFinite(val)) {
                    base += val;
                }
            }
        });

        const adjRaw = this._adjustmentInput ? Number(this._adjustmentInput.value) : 0;
        const adj = Number.isFinite(adjRaw) ? adjRaw : 0;
        const total = Math.max(0, Math.min(100, base + adj));

        this._totalScoreDisplay.textContent = String(total);
    }
}
