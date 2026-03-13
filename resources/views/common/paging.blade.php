<div class="node-list node-around" style="margin-top:100px;margin-bottom: 50px;">
    <div>
        @if (!$pager->onFirstPage())
            <div class="link-node-center fade">
                <a href="{{ $pager->previousPageUrl() }}">&lt;&lt; Prev</a>
            </div>
        @endif
    </div>
    <div>
        @if ($pager->hasMorePages())
            <div class="link-node-center fade">
                <a href="{{ $pager->nextPageUrl() }}">Next &gt;&gt;</a>
            </div>
        @endif
    </div>
</div>
