import { Component } from "../component";
import { HgnTree } from "../hgn-tree";

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

        this._scoreInputs?.forEach((input) => {
            const valueLabel = this.getScoreValueLabel(input);
            const parent = input.parentElement;
            const decreaseBtn = parent?.querySelector('.js-review-score-decrease') as HTMLButtonElement | null;
            const increaseBtn = parent?.querySelector('.js-review-score-increase') as HTMLButtonElement | null;
            const step = Number(input.step) || 5;
            const min = Number(input.min);
            const max = Number(input.max);

            const updateWithLabel: EventListener = () => {
                if (valueLabel) {
                    valueLabel.textContent = input.value;
                }
                if (decreaseBtn) {
                    decreaseBtn.disabled = Number(input.value) <= min;
                }
                if (increaseBtn) {
                    increaseBtn.disabled = Number(input.value) >= max;
                }
                this.updateTotalScore();
            };
            input.addEventListener('input', updateWithLabel);
            this._listeners.push({ el: input, type: 'input', fn: updateWithLabel });

            if (decreaseBtn) {
                const decreaseFn: EventListener = () => {
                    const newVal = Math.max(min, Number(input.value) - step);
                    input.value = String(newVal);
                    input.dispatchEvent(new Event('input'));
                };
                decreaseBtn.addEventListener('click', decreaseFn);
                this._listeners.push({ el: decreaseBtn, type: 'click', fn: decreaseFn });
            }

            if (increaseBtn) {
                const increaseFn: EventListener = () => {
                    const newVal = Math.min(max, Number(input.value) + step);
                    input.value = String(newVal);
                    input.dispatchEvent(new Event('input'));
                };
                increaseBtn.addEventListener('click', increaseFn);
                this._listeners.push({ el: increaseBtn, type: 'click', fn: increaseFn });
            }
        });

        if (this._adjustmentInput) {
            const adjInput = this._adjustmentInput;
            const adjMin = Number(adjInput.min);
            const adjMax = Number(adjInput.max);
            const adjStep = Number(adjInput.step) || 1;

            const parent = adjInput.parentElement;
            const decreaseBtn = parent?.querySelector('.js-review-adjustment-decrease') as HTMLButtonElement | null;
            const increaseBtn = parent?.querySelector('.js-review-adjustment-increase') as HTMLButtonElement | null;

            const adjustmentFn: EventListener = () => {
                if (this._adjustmentValueLabel && this._adjustmentInput) {
                    const val = Number(this._adjustmentInput.value);
                    this._adjustmentValueLabel.textContent = (val >= 0 ? '+' : '') + val;
                }
                if (decreaseBtn) {
                    decreaseBtn.disabled = Number(adjInput.value) <= adjMin;
                }
                if (increaseBtn) {
                    increaseBtn.disabled = Number(adjInput.value) >= adjMax;
                }
                this.updateTotalScore();
            };
            adjInput.addEventListener('input', adjustmentFn);
            this._listeners.push({ el: adjInput, type: 'input', fn: adjustmentFn });

            if (decreaseBtn) {
                const decreaseFn: EventListener = () => {
                    const newVal = Math.max(adjMin, Number(adjInput.value) - adjStep);
                    adjInput.value = String(newVal);
                    adjInput.dispatchEvent(new Event('input'));
                };
                decreaseBtn.addEventListener('click', decreaseFn);
                this._listeners.push({ el: decreaseBtn, type: 'click', fn: decreaseFn });
            }

            if (increaseBtn) {
                const increaseFn: EventListener = () => {
                    const newVal = Math.min(adjMax, Number(adjInput.value) + adjStep);
                    adjInput.value = String(newVal);
                    adjInput.dispatchEvent(new Event('input'));
                };
                increaseBtn.addEventListener('click', increaseFn);
                this._listeners.push({ el: increaseBtn, type: 'click', fn: increaseFn });
            }
        }

        if (this._form) {
            const form = this._form;
            const publishFn: EventListener = (e: Event) => {
                e.preventDefault();
                const currentNode = HgnTree.getInstance().currentNode;
                currentNode.changeChildNodesWithData(form.action, new FormData(form));
            };
            form.addEventListener('submit', publishFn);
            this._listeners.push({ el: form, type: 'submit', fn: publishFn });
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
