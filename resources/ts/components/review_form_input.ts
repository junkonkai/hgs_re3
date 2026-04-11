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
    private _scoreSelects: NodeListOf<HTMLSelectElement> | null = null;
    private _adjustmentInput: HTMLInputElement | null = null;
    private _listeners: Array<{ el: EventTarget; type: string; fn: EventListener }> = [];

    constructor(params: any | null = null)
    {
        super(params);

        this._form = document.querySelector('.js-review-form') as HTMLFormElement | null;
        this._draftSaveButton = document.querySelector('.js-review-draft-save') as HTMLButtonElement | null;
        this._totalScoreDisplay = document.querySelector('.js-review-total-score') as HTMLElement | null;
        this._fearMeterInput = document.querySelector('.js-fear-meter-value') as HTMLInputElement | null;
        this._scoreSelects = document.querySelectorAll('.js-review-score-select') as NodeListOf<HTMLSelectElement>;
        this._adjustmentInput = document.querySelector('.js-review-adjustment') as HTMLInputElement | null;

        this.updateTotalScore();

        const updateFn: EventListener = () => this.updateTotalScore();

        if (this._fearMeterInput) {
            this._fearMeterInput.addEventListener('input', updateFn);
            this._listeners.push({ el: this._fearMeterInput, type: 'input', fn: updateFn });
        }

        this._scoreSelects?.forEach(select => {
            select.addEventListener('change', updateFn);
            this._listeners.push({ el: select, type: 'change', fn: updateFn });
        });

        if (this._adjustmentInput) {
            this._adjustmentInput.addEventListener('input', updateFn);
            this._listeners.push({ el: this._adjustmentInput, type: 'input', fn: updateFn });
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

        this._scoreSelects?.forEach(select => {
            if (select.value !== '') {
                const val = Number(select.value);
                if (Number.isFinite(val)) {
                    base += val * 5;
                }
            }
        });

        const adjRaw = this._adjustmentInput ? Number(this._adjustmentInput.value) : 0;
        const adj = Number.isFinite(adjRaw) ? adjRaw : 0;
        const total = Math.max(0, Math.min(100, base + adj));

        this._totalScoreDisplay.textContent = String(total);
    }
}
