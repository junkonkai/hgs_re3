import { Component } from "../component";

export interface LineupSearchParams
{
    makerSuggestUrl: string;
}

/**
 * ラインナップ画面の検索フォーム（詳細検索の開閉・メーカーサジェスト・リセット）
 */
export class LineupSearch extends Component
{
    private _advancedToggle: HTMLElement | null = null;
    private _advancedWrapper: HTMLElement | null = null;
    private _advancedLabel: HTMLElement | null = null;
    private _advancedIcon: HTMLElement | null = null;
    private _nameInput: HTMLInputElement | null = null;
    private _idInput: HTMLInputElement | null = null;
    private _clearBtn: HTMLButtonElement | null = null;
    private _suggestions: HTMLElement | null = null;
    private _makerSuggestUrl: string = '';
    private _debounceTimer: ReturnType<typeof setTimeout> | null = null;
    private _isOpen: boolean = false;
    private _isAnimating: boolean = false;
    private _boundDocumentClick: ((e: MouseEvent) => void) | null = null;

    constructor(params: LineupSearchParams | null = null)
    {
        super(params);

        this._makerSuggestUrl = params?.makerSuggestUrl ?? '';
        if (!this._makerSuggestUrl) {
            return;
        }

        this._advancedToggle = document.getElementById('advanced-search-toggle');
        this._advancedWrapper = document.getElementById('advanced-search-wrapper');
        this._advancedLabel = document.getElementById('advanced-search-label');
        this._advancedIcon = document.getElementById('advanced-search-icon');
        this._nameInput = document.getElementById('maker-name-input') as HTMLInputElement | null;
        this._idInput = document.getElementById('maker-id-input') as HTMLInputElement | null;
        this._clearBtn = document.getElementById('maker-clear-btn') as HTMLButtonElement | null;
        this._suggestions = document.getElementById('maker-suggestions');

        if (!this._nameInput || !this._idInput || !this._clearBtn || !this._suggestions) {
            return;
        }

        this.setupAdvancedSearch();
        this.setupMakerSuggest();
        this.setupReset();
        this._boundDocumentClick = (e: MouseEvent) => this.handleDocumentClick(e);
        document.addEventListener('click', this._boundDocumentClick);
    }

    private setupAdvancedSearch(): void
    {
        if (!this._advancedToggle || !this._advancedWrapper || !this._advancedLabel || !this._advancedIcon) {
            return;
        }

        const domAlreadyOpen = this._advancedWrapper.classList.contains('open');
        if (domAlreadyOpen) {
            this._isOpen = true;
            this._advancedLabel.textContent = '閉じる';
            this._advancedIcon.textContent = '△';
        }
        /* 初期は閉じた状態。Grid 0fr/1fr のため overflow の操作は不要 */

        this._advancedToggle.addEventListener('click', () => {
            if (this._isAnimating) {
                return;
            }
            this.updateAdvancedState(!this._isOpen);
        });

        this._advancedWrapper.addEventListener('click', (e: MouseEvent) => {
            if (this._isAnimating) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    }

    private updateAdvancedState(open: boolean): void
    {
        if (!this._advancedWrapper || !this._advancedLabel || !this._advancedIcon) {
            return;
        }
        if (this._isAnimating) {
            return;
        }
        this._isAnimating = true;
        this._isOpen = open;
        if (open) {
            this._advancedWrapper.classList.add('open');
            this._advancedLabel.textContent = '閉じる';
            this._advancedIcon.textContent = '△';
        } else {
            this._advancedWrapper.classList.remove('open');
            this._advancedLabel.textContent = '開く';
            this._advancedIcon.textContent = '▽';
        }
        this._advancedWrapper.addEventListener('transitionend', () => {
            this._isAnimating = false;
        }, { once: true });
    }

    private setupMakerSuggest(): void
    {
        if (!this._nameInput || !this._idInput || !this._clearBtn || !this._suggestions) {
            return;
        }

        this._nameInput.addEventListener('input', () => {
            if (!this._idInput || !this._clearBtn || !this._suggestions) {
                return;
            }
            this._idInput.value = '';
            this._clearBtn.style.display = 'none';
            if (this._debounceTimer !== null) {
                clearTimeout(this._debounceTimer);
            }
            const q = (this._nameInput!.value ?? '').trim();
            if (q.length === 0) {
                this._suggestions.style.display = 'none';
                this._suggestions.innerHTML = '';
                return;
            }
            this._debounceTimer = setTimeout(() => this.fetchSuggestions(q), 250);
        });

        this._clearBtn.addEventListener('click', () => {
            if (!this._nameInput || !this._idInput || !this._clearBtn) {
                return;
            }
            this._nameInput.value = '';
            this._idInput.value = '';
            this._clearBtn.style.display = 'none';
        });
    }

    private fetchSuggestions(q: string): void
    {
        if (!this._suggestions) {
            return;
        }
        const url = this._makerSuggestUrl + '?q=' + encodeURIComponent(q);
        fetch(url)
            .then(res => res.json())
            .then((data: { makers?: { id: number; name: string }[] }) => {
                if (!this._suggestions) {
                    return;
                }
                this._suggestions.innerHTML = '';
                if (!data.makers || data.makers.length === 0) {
                    this._suggestions.style.display = 'none';
                    return;
                }
                data.makers.forEach(maker => {
                    const item = document.createElement('div');
                    item.className = 'maker-suggest-item';
                    item.textContent = maker.name;
                    item.addEventListener('click', () => this.selectMaker(maker));
                    this._suggestions!.appendChild(item);
                });
                this._suggestions.style.display = 'block';
            })
            .catch(() => {
                if (this._suggestions) {
                    this._suggestions.style.display = 'none';
                }
            });
    }

    private selectMaker(maker: { id: number; name: string }): void
    {
        if (!this._nameInput || !this._idInput || !this._clearBtn || !this._suggestions) {
            return;
        }
        this._nameInput.value = maker.name;
        this._idInput.value = String(maker.id);
        this._suggestions.style.display = 'none';
        this._clearBtn.style.display = '';
    }

    private setupReset(): void
    {
        const resetBtn = document.getElementById('search-reset-btn');
        if (!resetBtn || !this._nameInput || !this._idInput || !this._clearBtn) {
            return;
        }
        resetBtn.addEventListener('click', () => {
            const searchInput = document.getElementById('search-input') as HTMLInputElement | null;
            const platformSelect = document.querySelector<HTMLSelectElement>('[name="platform_id"]');
            const fearMin = document.getElementById('fear-meter-min') as HTMLSelectElement | null;
            const fearMax = document.getElementById('fear-meter-max') as HTMLSelectElement | null;
            const releaseFrom = document.querySelector<HTMLInputElement>('[name="release_from"]');
            const releaseTo = document.querySelector<HTMLInputElement>('[name="release_to"]');
            if (searchInput) {
                searchInput.value = '';
            }
            if (platformSelect) {
                platformSelect.value = '0';
            }
            this._nameInput!.value = '';
            this._idInput!.value = '';
            this._clearBtn!.style.display = 'none';
            if (fearMin) {
                fearMin.value = '';
            }
            if (fearMax) {
                fearMax.value = '';
            }
            if (releaseFrom) {
                releaseFrom.value = '';
            }
            if (releaseTo) {
                releaseTo.value = '';
            }
        });
    }

    private handleDocumentClick(e: MouseEvent): void
    {
        const target = e.target as Node;
        if (!this._nameInput || !this._suggestions) {
            return;
        }
        if (!this._nameInput.contains(target) && !this._suggestions.contains(target)) {
            this._suggestions.style.display = 'none';
        }
    }

    dispose(): void
    {
        if (this._boundDocumentClick) {
            document.removeEventListener('click', this._boundDocumentClick);
            this._boundDocumentClick = null;
        }
        if (this._debounceTimer !== null) {
            clearTimeout(this._debounceTimer);
            this._debounceTimer = null;
        }
    }
}
