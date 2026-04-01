@if ($pager->hasMultiplePages())
<nav class="mt-10 pager" aria-label="ページネーション">
    <div class="inline-flex flex-wrap items-center gap-x-2 gap-y-1">
        @if ($pager->showFirst())
            <a href="{{ $pager->firstPageUrl() }}" data-hgn-scope="{{ $pager->dataHgnScope() }}" class="pager-arrow" aria-label="1ページ目へ">&laquo;</a>
        @endif
        @foreach ($pager->pageNumbers() as $p)
            @if ($p === $pager->currentPage())
                <span class="pager-current" aria-current="page">{{ $p }}</span>
            @else
                <a href="{{ $pager->urlForPage($p) }}" data-hgn-scope="{{ $pager->dataHgnScope() }}" class="pager-num">{{ $p }}</a>
            @endif
        @endforeach
        @if ($pager->showLast())
            <a href="{{ $pager->lastPageUrl() }}" data-hgn-scope="{{ $pager->dataHgnScope() }}" class="pager-arrow" aria-label="最終ページへ">&raquo;</a>
        @endif
    </div>
</nav>
@endif
