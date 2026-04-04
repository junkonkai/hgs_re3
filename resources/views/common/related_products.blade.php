@if ($model->relatedProducts->count() > 0)
<section class="node tree-node" id="rp-tree-node">
    <div class="node-head node-head-small-margin">
        <h2 class="node-head-text">関連商品</h2>
        <span class="node-pt">●</span>
    </div>
    
    <div class="node-content basic">
        <p style="font-size: 12px;margin-bottom: 15px;">
            ショップによっては廉価版など商品の仕様が異なる場合があります。<br>
            中古商品もありますのでショップ側で商品情報をご確認ください。
        </p>
    </div>
    <div class="node-content tree">
        @foreach ($model->relatedProducts->sortByDesc('sort_order') as $rp)
        <section class="node" id="rp-{{ $rp->id }}-tree-node">
            <div class="node-head node-head-small-margin">
                <h3 class="node-head-text">{{ $rp->node_name }}</h3>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                <p>
                    {{ $rp->description }}
                </p>
                <div class="pkg-info">
                    @if ($rp->shops->count() > 0)
                    <div class="pkg-info-shops">
                        @foreach($rp->shops as $shop)
                        <div class="pkg-info-shop">
                            <a href="{{ $shop->url }}" target="_blank" ref="external noopener">
                                @if (!empty($shop->subtitle))
                                    <div class="shop-subtitle">{{ $shop->subtitle }}</div>
                                @endif
                                <div class="pkg-info-shop-img">
                                @if ($shop->ogp !== null && $shop->ogp->image !== null)
                                    <img src="{{ $shop->ogp->image }}" width="{{ $shop->ogp->image_width }}" height="{{ $shop->ogp->image_height }}" class="pkg-img">
                                @elseif (!empty($shop->img_tag))
                                    {!! $shop->img_tag !!}
                                @else
                                    <img src="{{ $rp->default_img_type->imgUrl() }}">
                                @endif
                                </div>
                                <div class="shop-name">
                                    {!! $shop->shop()?->name() ?? '--' !!}
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </section>
        @endforeach
    </div>
</section>
@endif
