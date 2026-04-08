@include ('admin.all_errors')
<table class="table admin-form-table" id="title-table">
    @if ($model->exists)
        <tr>
            <th>ID</th>
            <td>{{ $model->id }}</td>
        </tr>
    @endif
    <tr>
        <th>フランチャイズ</th>
        <td>
            <x-admin.select-game-franchise name="game_franchise_id" :model="$model" />
        </td>
    </tr>
        <tr>
            <th>シリーズ</th>
            <td>
                <x-admin.select-game-series name="game_series_id" :model="$model" />
            </td>
        </tr>
    <tr>
        <th>名前</th>
        <td>
            <x-admin.input name="name" :model="$model" required maxlength="200" />
        </td>
    </tr>
    <tr>
        <th>ノード表示用の名前</th>
        <td>
            <x-admin.textarea name="node_name" :model="$model" required maxlength="200" />
        </td>
    </tr>
    <tr>
        <th>key</th>
        <td>
            <x-admin.input name="key" :model="$model" required maxlength="50" />
        </td>
    </tr>
    <tr>
        <th>よみがな</th>
        <td>
            <x-admin.input name="phonetic" :model="$model" required maxlength="200" />
        </td>
    </tr>
    <tr>
        <th>俗称</th>
        <td>
            <x-admin.textarea name="search_synonyms" :model="$model" />
        </td>
    </tr>
    <tr>
        <th>レーティング</th>
        <td>
            <x-admin.select-enum name="rating" :model="$model" :list="App\Enums\Rating::selectList()" />
        </td>
    </tr>
    <tr>
        <th>説明文</th>
        <td>
            <x-admin.textarea name="description" :model="$model" />
            <button type="button" onclick="convertYoutubeIframe()" class="btn btn-sm btn-outline-secondary mt-1">YouTube iframe をレスポンシブ化</button>
            <script>
            function convertYoutubeIframe() {
                const textarea = document.getElementById('description');
                textarea.value = textarea.value.replace(
                    /<iframe([^>]*)><\/iframe>/gi,
                    function(match, attrs) {
                        const srcMatch    = attrs.match(/src="([^"]*)"/i);
                        const titleMatch  = attrs.match(/title="([^"]*)"/i);
                        const widthMatch  = attrs.match(/width="?(\d+)"?/i);
                        const heightMatch = attrs.match(/height="?(\d+)"?/i);
                        if (!srcMatch || !srcMatch[1].includes('youtube.com/embed')) return match;
                        const src      = srcMatch[1];
                        const title    = titleMatch ? titleMatch[1] : 'YouTube video player';
                        const w        = widthMatch  ? parseInt(widthMatch[1])  : 560;
                        const h        = heightMatch ? parseInt(heightMatch[1]) : 315;
                        const ratio    = (h / w * 100).toFixed(4);
                        return '<iframe src="' + src + '"'
                            + ' style="width:100%;max-width:' + w + 'px;aspect-ratio:' + w + '/' + h + ';"'
                            + ' title="' + title + '" frameborder="0"'
                            + ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"'
                            + ' referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>';
                    }
                );
            }
            </script>
        </td>
    </tr>
    <tr>
        <th>OGP URL</th>
        <td>
            <x-admin.input-ogp name="ogp_url" :model="$model" />
        </td>
    </tr>
    <tr>
        <th>説明文の引用元</th>
        <td>
            <x-admin.description-source name="description_source" :model="$model" />
        </td>
    </tr>
    <tr>
        <th>OGPの説明文を利用する</th>
        <td>
            <label class="form-check-label">
                <input type="checkbox" name="use_ogp_description" value="1" class="form-check-input me-1" @checked($model->use_ogp_description == 1)>
                利用する
            </label>
        </td>
    </tr>
    <tr>
        <th>最初のパッケージ発売日</th>
        <td>
            <x-admin.input name="first_release_int" :model="$model" type="number" min="0" max="99999999" />
            <p>パッケージと紐づけることで自動設定されるので変更不要</p>
        </td>
    </tr>
    <tr>
        <th>疑義</th>
        <td>
            <x-admin.textarea name="issue" :model="$model" />
        </td>
    </tr>
</table>

