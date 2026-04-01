import { Component } from "../component";

type ReactionKind = 'like' | 'report';

/**
 * 怖さメーターコメントのリアクション（いいね/通報）
 */
export class FearMeterCommentReaction extends Component
{
    private _forms: HTMLFormElement[] = [];
    private _isProcessingMap: WeakMap<HTMLFormElement, boolean> = new WeakMap();
    private _handlers: Map<HTMLFormElement, (e: SubmitEvent) => void> = new Map();

    constructor(params: any | null = null)
    {
        super(params);

        this._forms = Array.from(document.querySelectorAll('.fear-meter-reaction-form')) as HTMLFormElement[];
        if (this._forms.length === 0) {
            return;
        }

        this._forms.forEach((form) => {
            const handler = (e: SubmitEvent) => this.submit(e, form);
            form.addEventListener('submit', handler);
            this._handlers.set(form, handler);
            this._isProcessingMap.set(form, false);
        });
    }

    public dispose(): void
    {
        this._handlers.forEach((handler, form) => {
            form.removeEventListener('submit', handler);
        });
        this._handlers.clear();
        this._forms = [];
    }

    private async submit(e: SubmitEvent, form: HTMLFormElement): Promise<void>
    {
        e.preventDefault();

        if (this._isProcessingMap.get(form) === true) {
            return;
        }
        this._isProcessingMap.set(form, true);

        const kind = (form.dataset.reactionKind || '') as ReactionKind;
        const wasDone = form.dataset.done === '1';
        const countElement = form.querySelector('.js-like-count') as HTMLElement | null;
        const beforeCount = countElement ? parseInt(countElement.textContent || '0', 10) : 0;
        const currentHtml = form.innerHTML;
        if (kind === 'like' && countElement) {
            const afterCount = wasDone ? Math.max(0, beforeCount - 1) : beforeCount + 1;
            countElement.textContent = String(afterCount);
            form.dataset.done = wasDone ? '0' : '1';
        }
        if (kind === 'report' && !wasDone) {
            form.dataset.done = '1';
            this.updateReportBadge(form, true);
        }

        try {
            let requestUrl = form.action;
            let requestMethod = 'POST';
            let requestBody: FormData = new FormData(form);
            const requestHeaders: Record<string, string> = {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            };

            if (kind === 'like') {
                if (wasDone) {
                    requestUrl = form.dataset.unlikeUrl || form.action;
                    requestBody = new FormData(form);
                } else {
                    requestUrl = form.dataset.likeUrl || form.action;
                    requestBody = new FormData(form);
                }
            }

            const response = await fetch(requestUrl, {
                method: requestMethod,
                headers: requestHeaders,
                credentials: 'same-origin',
                body: requestBody
            });

            if (!response.ok) {
                throw new Error('request failed');
            }

            if (kind === 'like') {
                form.dataset.done = wasDone ? '0' : '1';
            }
            if (kind === 'report') {
                form.dataset.done = '1';
                this.updateReportBadge(form, true);
            }
        } catch (error) {
            form.innerHTML = currentHtml;
            form.dataset.done = wasDone ? '1' : '0';
            if (kind === 'like' && countElement) {
                const restored = form.querySelector('.js-like-count') as HTMLElement | null;
                if (restored) {
                    restored.textContent = String(beforeCount);
                }
            }
            if (kind === 'report') {
                this.updateReportBadge(form, wasDone);
            }
            alert('リアクションの送信に失敗しました。');
        } finally {
            await new Promise(resolve => setTimeout(resolve, 250));
            this._isProcessingMap.set(form, false);
        }
    }

    private updateReportBadge(form: HTMLFormElement, done: boolean): void
    {
        const button = form.querySelector('button[type="submit"]') as HTMLButtonElement | null;
        if (!button) {
            return;
        }
        button.textContent = done ? '通報済み' : '通報';
        if (done) {
            button.disabled = true;
            button.classList.add('opacity-70');
        } else {
            button.disabled = false;
            button.classList.remove('opacity-70');
        }
    }
}
