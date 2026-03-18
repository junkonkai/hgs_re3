<?php

namespace App\Http\Controllers\Api\Admin\Game;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Game\PlatformRequest;
use App\Models\GamePlatform;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlatformController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        if ($perPage < 1) {
            $perPage = 1;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $q = trim((string) $request->query('q', ''));

        $platforms = GamePlatform::query()->orderByDesc('id');
        $platforms = $this->applySearchQuery($platforms, $q);

        $paginator = $platforms->paginate($perPage)->appends($request->query());

        $data = collect($paginator->items())
            ->map(fn (GamePlatform $platform) => $this->platformToArray($platform))
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
        $platform = GamePlatform::query()
            ->with(['synonyms:game_platform_id,synonym'])
            ->find($id);

        if ($platform === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $this->platformToArray($platform),
        ]);
    }

    public function store(PlatformRequest $request): JsonResponse
    {
        $platform = new GamePlatform();
        $platform->fill($request->validated());
        $platform->synonymsStr = $request->validated('synonymsStr', '');
        $platform->save();

        $platform->load(['synonyms:game_platform_id,synonym']);

        return response()->json([
            'data' => $this->platformToArray($platform),
        ], Response::HTTP_CREATED);
    }

    public function update(PlatformRequest $request, int $id): JsonResponse
    {
        $platform = GamePlatform::query()->find($id);

        if ($platform === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $platform->fill($request->validated());
        $platform->synonymsStr = $request->validated('synonymsStr', '');
        $platform->save();

        $platform->load(['synonyms:game_platform_id,synonym']);

        return response()->json([
            'data' => $this->platformToArray($platform),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $platform = GamePlatform::query()->find($id);

        if ($platform === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $platform->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function relatedProductsIndex(int $id): JsonResponse
    {
        $platform = GamePlatform::query()->find($id);

        if ($platform === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $platform->relatedProducts()
            ->orderBy('game_related_products.id')
            ->get(['game_related_products.id', 'game_related_products.name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function relatedProductsAttach(Request $request, int $id): JsonResponse
    {
        $platform = GamePlatform::query()->find($id);

        if ($platform === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_related_product_ids' => 'required|array',
            'game_related_product_ids.*' => 'integer|exists:game_related_products,id',
        ]);
        $ids = $validated['game_related_product_ids'] ?? [];
        if ($ids !== []) {
            $platform->relatedProducts()->syncWithoutDetaching($ids);
        }

        return response()->json(['message' => 'OK']);
    }

    public function relatedProductsSync(Request $request, int $id): JsonResponse
    {
        $platform = GamePlatform::query()->find($id);

        if ($platform === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_related_product_ids' => 'nullable|array',
            'game_related_product_ids.*' => 'integer|exists:game_related_products,id',
        ]);
        $ids = $validated['game_related_product_ids'] ?? [];
        $platform->relatedProducts()->sync($ids);

        return response()->json(['message' => 'OK']);
    }

    public function relatedProductsDetach(int $id, int $relatedProductId): JsonResponse
    {
        $platform = GamePlatform::query()->find($id);

        if ($platform === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $platform->relatedProducts()->detach([$relatedProductId]);

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

        $query->where(function (Builder $sub) use ($words)
        {
            foreach ($words as $word) {
                $sub->where('name', 'LIKE', '%' . $word . '%');
            }
        });

        $synonyms = array_map(fn (string $w) => synonym($w), $words);

        $query->orWhereIn('id', function ($sub) use ($synonyms)
        {
            $sub->select('game_platform_id')
                ->from('game_platform_synonyms')
                ->whereIn('synonym', $synonyms);
        });

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function platformToArray(GamePlatform $platform): array
    {
        $synonyms = $platform->relationLoaded('synonyms')
            ? $platform->synonyms->pluck('synonym')->values()->all()
            : [];

        $type = $platform->type;
        if ($type instanceof \BackedEnum) {
            $type = $type->value;
        }

        return [
            'id' => $platform->id,
            'name' => $platform->name,
            'key' => $platform->key,
            'acronym' => $platform->acronym,
            'node_name' => $platform->node_name,
            'type' => $type,
            'sort_order' => $platform->sort_order,
            'game_maker_id' => $platform->game_maker_id,
            'description' => $platform->description,
            'description_source' => $platform->description_source,
            'synonyms' => $synonyms,
        ];
    }

    /**
     * @return array<string, int|null>
     */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function paginationLinks(LengthAwarePaginator $paginator): array
    {
        return [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];
    }
}
