<?php

namespace App\Http\Controllers\Api\Admin\Game;

use App\Http\Controllers\Api\Admin\Game\Concerns\PaginatesAdminGameApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Game\RelatedProductRequest;
use App\Http\Requests\Api\Admin\Game\RelatedProductShopRequest;
use App\Models\GameRelatedProduct;
use App\Models\GameRelatedProductShop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RelatedProductController extends Controller
{
    use PaginatesAdminGameApi;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPageFromRequest($request);
        $q = trim((string) $request->query('q', ''));

        $query = GameRelatedProduct::query()->orderByDesc('id');
        $query = $this->applySearchQuery($query, $q);

        $paginator = $query->paginate($perPage)->appends($request->query());

        $data = collect($paginator->items())
            ->map(fn (GameRelatedProduct $rp) => $this->relatedProductToArray($rp))
            ->values()
            ->all();

        return response()->json([
            'data' => $data,
            'meta' => $this->paginationMeta($paginator),
            'links' => $this->paginationLinks($paginator),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->relatedProductToArray($rp)]);
    }

    public function store(RelatedProductRequest $request): JsonResponse
    {
        $rp = new GameRelatedProduct();
        $rp->fill($request->validated());
        $rp->save();

        return response()->json([
            'data' => $this->relatedProductToArray($rp),
        ], Response::HTTP_CREATED);
    }

    public function update(RelatedProductRequest $request, int $id): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rp->fill($request->validated());
        $rp->save();

        return response()->json(['data' => $this->relatedProductToArray($rp)]);
    }

    public function destroy(int $id): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rp->titles()->detach();
        $rp->mediaMixes()->detach();
        $rp->platforms()->detach();
        $rp->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function platformsIndex(int $id): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $rp->platforms()
            ->orderBy('game_platforms.id')
            ->get(['game_platforms.id', 'game_platforms.name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function platformsAttach(Request $request, int $id): JsonResponse
    {
        return $this->mutatePlatforms($request, $id, true);
    }

    public function platformsSync(Request $request, int $id): JsonResponse
    {
        return $this->mutatePlatforms($request, $id, false);
    }

    private function mutatePlatforms(Request $request, int $id, bool $attachOnly): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        if ($attachOnly) {
            $validated = $request->validate([
                'game_platform_ids' => 'required|array',
                'game_platform_ids.*' => 'integer|exists:game_platforms,id',
            ]);
            $ids = $validated['game_platform_ids'] ?? [];
            if ($ids !== []) {
                $rp->platforms()->syncWithoutDetaching($ids);
            }
        } else {
            $validated = $request->validate([
                'game_platform_ids' => 'nullable|array',
                'game_platform_ids.*' => 'integer|exists:game_platforms,id',
            ]);
            $rp->platforms()->sync($validated['game_platform_ids'] ?? []);
        }

        return response()->json(['message' => 'OK']);
    }

    public function platformsDetach(int $id, int $platformId): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rp->platforms()->detach([$platformId]);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function titlesIndex(int $id): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $rp->titles()
            ->orderBy('game_titles.id')
            ->get(['game_titles.id', 'game_titles.name'])
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function titlesAttach(Request $request, int $id): JsonResponse
    {
        return $this->mutateTitles($request, $id, true);
    }

    public function titlesSync(Request $request, int $id): JsonResponse
    {
        return $this->mutateTitles($request, $id, false);
    }

    private function mutateTitles(Request $request, int $id, bool $attachOnly): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        if ($attachOnly) {
            $validated = $request->validate([
                'game_title_ids' => 'required|array',
                'game_title_ids.*' => 'integer|exists:game_titles,id',
            ]);
            $ids = $validated['game_title_ids'] ?? [];
            if ($ids !== []) {
                $rp->titles()->syncWithoutDetaching($ids);
            }
        } else {
            $validated = $request->validate([
                'game_title_ids' => 'nullable|array',
                'game_title_ids.*' => 'integer|exists:game_titles,id',
            ]);
            $rp->titles()->sync($validated['game_title_ids'] ?? []);
        }

        return response()->json(['message' => 'OK']);
    }

    public function titlesDetach(int $id, int $titleId): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rp->titles()->detach([$titleId]);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function mediaMixesIndex(int $id): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $rp->mediaMixes()
            ->orderBy('game_media_mixes.id')
            ->get(['game_media_mixes.id', 'game_media_mixes.name'])
            ->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function mediaMixesAttach(Request $request, int $id): JsonResponse
    {
        return $this->mutateMediaMixes($request, $id, true);
    }

    public function mediaMixesSync(Request $request, int $id): JsonResponse
    {
        return $this->mutateMediaMixes($request, $id, false);
    }

    private function mutateMediaMixes(Request $request, int $id, bool $attachOnly): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        if ($attachOnly) {
            $validated = $request->validate([
                'game_media_mix_ids' => 'required|array',
                'game_media_mix_ids.*' => 'integer|exists:game_media_mixes,id',
            ]);
            $ids = $validated['game_media_mix_ids'] ?? [];
            if ($ids !== []) {
                $rp->mediaMixes()->syncWithoutDetaching($ids);
            }
        } else {
            $validated = $request->validate([
                'game_media_mix_ids' => 'nullable|array',
                'game_media_mix_ids.*' => 'integer|exists:game_media_mixes,id',
            ]);
            $rp->mediaMixes()->sync($validated['game_media_mix_ids'] ?? []);
        }

        return response()->json(['message' => 'OK']);
    }

    public function mediaMixesDetach(int $id, int $mediaMixId): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rp->mediaMixes()->detach([$mediaMixId]);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function shopsIndex(int $id): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $rp->shops()
            ->orderBy('id')
            ->get()
            ->map(fn (GameRelatedProductShop $s) => $this->relatedProductShopToArray($s))
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function shopsShow(int $id, int $shopId): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $shop = $rp->shops()->where('id', $shopId)->first();

        if ($shop === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->relatedProductShopToArray($shop)]);
    }

    public function shopsStore(RelatedProductShopRequest $request, int $id): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $shop = new GameRelatedProductShop();
        $shop->game_related_product_id = $rp->id;
        $shop->fill($request->validated());
        $shop->setOgpInfo($request->input('ogp_url'));
        $shop->save();

        return response()->json([
            'data' => $this->relatedProductShopToArray($shop),
        ], Response::HTTP_CREATED);
    }

    public function shopsUpdate(RelatedProductShopRequest $request, int $id, int $shopId): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $shop = $rp->shops()->where('id', $shopId)->first();

        if ($shop === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $shop->fill($request->validated());
        $shop->setOgpInfo($request->input('ogp_url'));
        $shop->save();

        return response()->json(['data' => $this->relatedProductShopToArray($shop)]);
    }

    public function shopsDestroy(int $id, int $shopId): JsonResponse
    {
        $rp = GameRelatedProduct::query()->find($id);

        if ($rp === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $shop = $rp->shops()->where('id', $shopId)->first();

        if ($shop === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $shop->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function applySearchQuery(Builder $query, string $q): Builder
    {
        if ($q === '') {
            return $query;
        }

        $words = array_values(array_filter(preg_split('/\s+/u', $q) ?: [], fn ($w) => $w !== ''));

        if ($words === []) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($words)
        {
            foreach ($words as $word) {
                $sub->where('name', 'LIKE', '%' . $word . '%');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function relatedProductToArray(GameRelatedProduct $rp): array
    {
        return [
            'id' => $rp->id,
            'name' => $rp->name,
            'node_name' => $rp->node_name,
            'rating' => $rp->rating instanceof \BackedEnum ? $rp->rating->value : $rp->rating,
            'default_img_type' => $rp->default_img_type instanceof \BackedEnum
                ? $rp->default_img_type->value
                : $rp->default_img_type,
            'description' => $rp->description,
            'description_source' => $rp->description_source,
            'sort_order' => $rp->sort_order,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function relatedProductShopToArray(GameRelatedProductShop $s): array
    {
        return [
            'id' => $s->id,
            'game_related_product_id' => $s->game_related_product_id,
            'shop_id' => $s->shop_id,
            'subtitle' => $s->subtitle,
            'url' => $s->url,
            'img_tag' => $s->img_tag,
            'param1' => $s->param1,
            'param2' => $s->param2,
            'param3' => $s->param3,
            'use_img_tag' => $s->use_img_tag,
        ];
    }
}
