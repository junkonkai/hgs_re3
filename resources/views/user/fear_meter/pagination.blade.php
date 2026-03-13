@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $windowStart = max(1, $current - 2);
        $windowEnd = min($last, $current + 2);

        $pages = collect([1]);
        if ($last > 1) {
            $pages->push($last);
        }
        for ($p = $windowStart; $p <= $windowEnd; $p++) {
            $pages->push($p);
        }
        $pages = $pages->unique()->sort()->values();
    @endphp
    <nav class="flex items-center gap-3 flex-wrap" aria-label="ページネーション">
        {{-- 1つ前 --}}
        @if ($paginator->onFirstPage())
            <span class="text-gray-400" aria-disabled="true">&lt;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" data-hgn-scope="full" class="hover:underline">&lt;</a>
        @endif

        @foreach ($pages as $i => $page)
            @if ($i > 0 && $page - $pages[$i - 1] > 1)
                <span class="px-1">....</span>
            @endif
            @if ($page === $current)
                <span class="font-bold" aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" data-hgn-scope="full" class="hover:underline">{{ $page }}</a>
            @endif
        @endforeach

        {{-- 1つ次 --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" data-hgn-scope="full" class="hover:underline">&gt;</a>
        @else
            <span class="text-gray-400" aria-disabled="true">&gt;</span>
        @endif
    </nav>
@endif
