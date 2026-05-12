import { Component } from "../component";

type ReactionKind = 'like' | 'report';

/**
 * レビューのリアクション（いいね/通報）
 */
export class ReviewReaction extends Component
{
    private _forms: HTMLFormElement[] = [];
    private _isProcessingMap: WeakMap<HTMLFormElement, boolean> = new WeakMap();
    private _handlers: Map<HTMLFormElement, (e: SubmitEvent) => void> = new Map();
    private _reportOpenHandlers: Map<HTMLButtonElement, () => void> = new Map();
    private _reportCancelHandlers: Map<HTMLButtonElement, () => void> = new Map();

    constructor(params: any | null = null)
    {
        super(params);

        this._forms = Array.from(document.querySelectorAll('.review-reaction-form')) as HTMLFormElement[];

        this._forms.forEach((form) => {
            const handler = (e: SubmitEvent) => this.submit(e, form);
            form.addEventListener('submit', handler);
            this._handlers.set(form, handler);
            this._isProcessingMap.set(form, false);
        });

        // 通報モーダルを開くボタン
        const openButtons = Array.from(document.querySelectorAll('.js-review-report-open')) as HTMLButtonElement[];
        openButtons.forEach((btn) => {
            const handler = () => this.openReportModal(btn);
            btn.addEventListener('click', handler);
            this._reportOpenHandlers.set(btn, handler);
        });

        // 通報モーダルのキャンセルボタン
        const cancelButtons = Array.from(document.querySelectorAll('.js-report-modal-cancel')) as HTMLButtonElement[];
        cancelButtons.forEach((btn) => {
            const handler = () => this.closeReportModal(btn);
            btn.addEventListener('click', handler);
            this._reportCancelHandlers.set(btn, handler);
        });
    }

    public dispose(): void
    {
        this._handlers.forEach((handler, form) => {
            form.removeEventListener('submit', handler);
        });
        this._handlers.clear();
        this._forms = [];

        this._reportOpenHandlers.forEach((handler, btn) => {
            btn.removeEventListener('click', handler);
        });
        this._reportOpenHandlers.clear();

        this._reportCancelHandlers.forEach((handler, btn) => {
            btn.removeEventListener('click', handler);
        });
        this._reportCancelHandlers.clear();
    }

    private openReportModal(btn: HTMLButtonElement): void
    {
        const modalId = btn.dataset.modalId;
        if (!modalId) {
            return;
        }
        const modal = document.getElementById(modalId) as HTMLDialogElement | null;
        modal?.showModal();
    }

    private closeReportModal(btn: HTMLButtonElement): void
    {
        const modal = btn.closest('dialog') as HTMLDialogElement | null;
        modal?.close();
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
        // 通報はモーダル経由のため楽観的更新はしない
        if (kind === 'report') {
            const submitButton = form.querySelector('button[type="submit"]') as HTMLButtonElement | null;
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = '送信中...';
            }
        }

        try {
            let requestUrl = form.action;
            const requestBody: FormData = new FormData(form);
            const requestHeaders: Record<string, string> = {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            };

            if (kind === 'like') {
                requestUrl = wasDone
                    ? (form.dataset.unlikeUrl || form.action)
                    : (form.dataset.likeUrl || form.action);
            }

            const response = await fetch(requestUrl, {
                method: 'POST',
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
                this.closeModalAndMarkReported(form);
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
                const submitButton = form.querySelector('button[type="submit"]') as HTMLButtonElement | null;
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = '通報する';
                }
            }
            alert('リアクションの送信に失敗しました。');
        } finally {
            await new Promise(resolve => setTimeout(resolve, 250));
            this._isProcessingMap.set(form, false);
        }
    }

    private closeModalAndMarkReported(form: HTMLFormElement): void
    {
        // モーダルを閉じる
        const modalId = form.dataset.modalId;
        if (modalId) {
            const modal = document.getElementById(modalId) as HTMLDialogElement | null;
            modal?.close();
        }

        // 通報ボタンを「通報済み」スパンに差し替え
        if (modalId) {
            const openBtn = document.querySelector<HTMLButtonElement>(`.js-review-report-open[data-modal-id="${modalId}"]`);
            if (openBtn) {
                const badge = document.createElement('span');
                badge.className = 'ml-auto inline-flex h-6 items-center gap-1 leading-none text-slate-500';
                badge.innerHTML = '<i class="bi bi-flag-fill"></i> 通報済み';
                openBtn.replaceWith(badge);
            }
        }
    }
}
