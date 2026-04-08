import { Component } from "../component";

/**
 * 6桁OTPコード入力コンポーネント
 * - ページ内の .js-otp-input-wrapper を全て対象にする
 * - 1桁入力で次の枠へ自動フォーカス移動
 * - 6桁揃ったら自動でフォーム送信
 * - ペーストで6枠に展開
 * - Backspaceで前の枠に戻る
 */
export class OtpInput extends Component
{
    constructor(params: any | null = null)
    {
        super(params);

        const wrappers = Array.from(
            document.querySelectorAll<HTMLElement>('.js-otp-input-wrapper')
        );

        wrappers.forEach(wrapper => this.initWrapper(wrapper));
    }

    public dispose(): void
    {
        // イベントリスナーは要素ごと破棄されるため個別解除不要
    }

    private initWrapper(wrapper: HTMLElement): void
    {
        const digits = Array.from(wrapper.querySelectorAll<HTMLInputElement>('.js-otp-digit'));
        const hidden = wrapper.querySelector<HTMLInputElement>('.js-otp-hidden');
        const form = wrapper.closest('form') as HTMLFormElement | null;

        if (digits.length !== 6 || !hidden || !form) {
            return;
        }

        const syncHidden = () => {
            hidden.value = digits.map(d => d.value).join('');
        };

        const isFilled = () => digits.every(d => d.value !== '');

        digits.forEach((input, index) => {
            input.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'Backspace') {
                    if (input.value === '' && index > 0) {
                        const prev = digits[index - 1];
                        prev.value = '';
                        prev.focus();
                        syncHidden();
                    }
                } else if (e.key === 'ArrowLeft' && index > 0) {
                    digits[index - 1].focus();
                } else if (e.key === 'ArrowRight' && index < digits.length - 1) {
                    digits[index + 1].focus();
                }
            });

            input.addEventListener('input', () => {
                const raw = input.value.replace(/\D/g, '');
                input.value = raw.slice(-1);

                syncHidden();

                if (input.value !== '' && index < digits.length - 1) {
                    digits[index + 1].focus();
                }

                if (isFilled()) {
                    form.requestSubmit();
                }
            });

            input.addEventListener('paste', (e: ClipboardEvent) => {
                e.preventDefault();
                const text = e.clipboardData?.getData('text') ?? '';
                const chars = text.replace(/\D/g, '').slice(0, 6);

                chars.split('').forEach((ch, i) => {
                    if (digits[i]) {
                        digits[i].value = ch;
                    }
                });

                const nextIndex = Math.min(chars.length, digits.length - 1);
                digits[nextIndex].focus();

                syncHidden();

                if (isFilled()) {
                    form.requestSubmit();
                }
            });

            input.addEventListener('focus', () => input.select());
        });
    }
}
