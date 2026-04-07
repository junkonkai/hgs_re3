import { Component } from "../component";

/**
 * 6桁OTPコード入力コンポーネント
 * - 1桁入力で次の枠へ自動フォーカス移動
 * - 6桁揃ったら自動でフォーム送信
 * - ペーストで6枠に展開
 * - Backspaceで前の枠に戻る
 */
export class OtpInput extends Component
{
    private _wrapper: HTMLElement | null = null;
    private _digits: HTMLInputElement[] = [];
    private _hidden: HTMLInputElement | null = null;
    private _form: HTMLFormElement | null = null;

    constructor(params: any | null = null)
    {
        super(params);

        this._wrapper = document.querySelector('.js-otp-input-wrapper') as HTMLElement | null;
        if (!this._wrapper) {
            return;
        }

        this._digits = Array.from(this._wrapper.querySelectorAll<HTMLInputElement>('.js-otp-digit'));
        this._hidden = this._wrapper.querySelector<HTMLInputElement>('.js-otp-hidden');
        this._form = this._wrapper.closest('form') as HTMLFormElement | null;

        if (this._digits.length !== 6 || !this._hidden || !this._form) {
            return;
        }

        this._digits.forEach((input, index) => {
            input.addEventListener('keydown', (e) => this.onKeyDown(e, index));
            input.addEventListener('input', (e) => this.onInput(e, index));
            input.addEventListener('paste', (e) => this.onPaste(e));
            input.addEventListener('focus', () => input.select());
        });
    }

    public dispose(): void
    {
        // イベントリスナーは要素ごと破棄されるため個別解除不要
    }

    private onKeyDown(e: KeyboardEvent, index: number): void
    {
        const input = this._digits[index];

        if (e.key === 'Backspace') {
            if (input.value === '' && index > 0) {
                // 空の枠でBackspaceなら前の枠に戻ってクリア
                const prev = this._digits[index - 1];
                prev.value = '';
                prev.focus();
                this.syncHidden();
            }
        } else if (e.key === 'ArrowLeft' && index > 0) {
            this._digits[index - 1].focus();
        } else if (e.key === 'ArrowRight' && index < this._digits.length - 1) {
            this._digits[index + 1].focus();
        }
    }

    private onInput(e: Event, index: number): void
    {
        const input = this._digits[index];

        // 数字以外を除去し1文字に制限
        const raw = input.value.replace(/\D/g, '');
        input.value = raw.slice(-1);

        this.syncHidden();

        if (input.value !== '' && index < this._digits.length - 1) {
            this._digits[index + 1].focus();
        }

        if (this.isFilled()) {
            this._form?.requestSubmit();
        }
    }

    private onPaste(e: ClipboardEvent): void
    {
        e.preventDefault();
        const text = e.clipboardData?.getData('text') ?? '';
        const digits = text.replace(/\D/g, '').slice(0, 6);

        digits.split('').forEach((ch, i) => {
            if (this._digits[i]) {
                this._digits[i].value = ch;
            }
        });

        // フォーカスを最後に入力した枠の次、または最後の枠に移動
        const nextIndex = Math.min(digits.length, this._digits.length - 1);
        this._digits[nextIndex].focus();

        this.syncHidden();

        if (this.isFilled()) {
            this._form?.requestSubmit();
        }
    }

    private syncHidden(): void
    {
        if (!this._hidden) {
            return;
        }
        this._hidden.value = this._digits.map(d => d.value).join('');
    }

    private isFilled(): boolean
    {
        return this._digits.every(d => d.value !== '');
    }
}
