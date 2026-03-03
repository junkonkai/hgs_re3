@foreach ($franchises as $franchise)
<section class="node link-tree-node" id="franchise-{{ $franchise->key }}-link-node">
    <div class="node-head">
        <a href="{{ route('Game.FranchiseDetail', ['franchiseKey' => $franchise->key]) }}" class="node-head-text">{{ $franchise->name }} フランチャイズ</a>
        <span class="node-pt">●</span>
    </div>
    <div class="node-content tree">
        @foreach ($franchise->searchSeries ?? [] as $series)
        <section class="node tree-node">
            <div class="node-head">
                <h3 class="node-head-text text-muted">{{ $series->name }} シリーズ</h3>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content tree">
                @foreach ($series->searchTitles ?? [] as $title)
                <section class="node link-node" id="{{ $title->key }}-link-node">
                    <div class="node-head">
                        <a href="{{ route('Game.TitleDetail', ['titleKey' => $title->key]) }}" class="node-head-text">{{ $title->name }}</a>
                        <span class="node-pt">●</span>
                    </div>
                </section>
                @endforeach
            </div>
        </section>
        @endforeach
        @foreach ($franchise->searchTitles ?? [] as $title)
        <section class="node link-node" id="{{ $title->key }}-link-node">
            <div class="node-head">
                <a href="{{ route('Game.TitleDetail', ['titleKey' => $title->key]) }}" class="node-head-text">{{ $title->name }}</a>
                <span class="node-pt">●</span>
            </div>
        </section>
        @endforeach
    </div>
</section>
@endforeach
@if (!empty($hasMore))
<section class="node load-more-node" id="lineup-load-more-node" data-load-more-url="{{ route('Game.LineupMore') }}?page={{ $nextPage }}">
    <div class="node-head">
        <span class="node-head-text">さらに表示</span>
        <span class="node-pt">●</span>
    </div>
</section>
@endif
