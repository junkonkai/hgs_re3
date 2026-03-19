<?php

namespace App\Http\Controllers\Api\Admin\Game;

use App\Http\Controllers\Api\Admin\Game\Concerns\PaginatesAdminGameApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Game\MediaMixRequest;
use App\Models\GameMediaMix;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MediaMixController extends Controller
{
    use PaginatesAdminGameApi;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPageFromRequest($request);
        $q = trim((string) $request->query('q', ''));

        $query = GameMediaMix::query()->orderByDesc('id');
        $query = $this->applySearchQuery($query, $q);

        $paginator = $query->paginate($perPage)->appends($request->query());

        $data = collect($paginator->items())
            ->map(fn (GameMediaMix $m) => $this->mediaMixToArray($m))
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
        $mm = GameMediaMix::query()->find($id);

        if ($mm === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->mediaMixToArray($mm)]);
    }

    public function store(MediaMixRequest $request): JsonResponse
    {
        $mm = new GameMediaMix();
        $mm->fill($request->validated());
        $mm->setOgpInfo($request->input('ogp_url'));
        $mm->save();

        return response()->json([
            'data' => $this->mediaMixToArray($mm->fresh()),
        ], Response::HTTP_CREATED);
    }

    public function update(MediaMixRequest $request, int $id): JsonResponse
    {
        $mm = GameMediaMix::query()->find($id);

        if ($mm === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $mm->fill($request->validated());
        $mm->setOgpInfo($request->input('ogp_url'));
        $mm->save();

        return response()->json(['data' => $this->mediaMixToArray($mm->fresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        $mm = GameMediaMix::query()->find($id);

        if ($mm === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $mm->titles()->detach();
        $mm->relatedProducts()->detach();
        $mm->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function titlesIndex(int $id): JsonResponse
    {
        $mm = GameMediaMix::query()->find($id);

        if ($mm === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $mm->titles()
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
        $mm = GameMediaMix::query()->find($id);

        if ($mm === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        if ($attachOnly) {
            $validated = $request->validate([
                'game_title_ids' => 'required|array',
                'game_title_ids.*' => 'integer|exists:game_titles,id',
            ]);
            $ids = $validated['game_title_ids'] ?? [];
            if ($ids !== []) {
                $mm->titles()->syncWithoutDetaching($ids);
            }
        } else {
            $validated = $request->validate([
                'game_title_ids' => 'nullable|array',
                'game_title_ids.*' => 'integer|exists:game_titles,id',
            ]);
            $mm->titles()->sync($validated['game_title_ids'] ?? []);
        }

        return response()->json(['message' => 'OK']);
    }

    public function titlesDetach(int $id, int $titleId): JsonResponse
    {
        $mm = GameMediaMix::query()->find($id);

        if ($mm === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $mm->titles()->detach([$titleId]);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function relatedProductsIndex(int $id): JsonResponse
    {
        $mm = GameMediaMix::query()->find($id);

        if ($mm === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $mm->relatedProducts()
            ->orderBy('game_related_products.id')
            ->get(['game_related_products.id', 'game_related_products.name'])
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function relatedProductsAttach(Request $request, int $id): JsonResponse
    {
        return $this->mutateRelatedProducts($request, $id, true);
    }

    public function relatedProductsSync(Request $request, int $id): JsonResponse
    {
        return $this->mutateRelatedProducts($request, $id, false);
    }

    private function mutateRelatedProducts(Request $request, int $id, bool $attachOnly): JsonResponse
    {
        $mm = GameMediaMix::query()->find($id);

        if ($mm === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        if ($attachOnly) {
            $validated = $request->validate([
                'game_related_product_ids' => 'required|array',
                'game_related_product_ids.*' => 'integer|exists:game_related_products,id',
            ]);
            $ids = $validated['game_related_product_ids'] ?? [];
            if ($ids !== []) {
                $mm->relatedProducts()->syncWithoutDetaching($ids);
            }
        } else {
            $validated = $request->validate([
                'game_related_product_ids' => 'nullable|array',
                'game_related_product_ids.*' => 'integer|exists:game_related_products,id',
            ]);
            $mm->relatedProducts()->sync($validated['game_related_product_ids'] ?? []);
        }

        return response()->json(['message' => 'OK']);
    }

    public function relatedProductsDetach(int $id, int $relatedProductId): JsonResponse
    {
        $mm = GameMediaMix::query()->find($id);

        if ($mm === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $mm->relatedProducts()->detach([$relatedProductId]);

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
    private function mediaMixToArray(GameMediaMix $m): array
    {
        return [
            'id' => $m->id,
            'type' => $m->type instanceof \BackedEnum ? $m->type->value : $m->type,
            'name' => $m->name,
            'key' => $m->key,
            'node_name' => $m->node_name,
            'game_franchise_id' => $m->game_franchise_id,
            'game_media_mix_group_id' => $m->game_media_mix_group_id,
            'rating' => $m->rating instanceof \BackedEnum ? $m->rating->value : $m->rating,
            'sort_order' => $m->sort_order,
            'description' => $m->description,
            'description_source' => $m->description_source,
            'use_ogp_description' => (bool) $m->use_ogp_description,
        ];
    }
}
