<?php

namespace App\Http\Controllers\Api\Admin\Game;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Game\MakerRequest;
use App\Models\GameMaker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MakerController extends Controller
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

        $makers = GameMaker::query()->orderByDesc('id');
        $makers = $this->applySearchQuery($makers, $q);

        $paginator = $makers->paginate($perPage)->appends($request->query());

        $data = collect($paginator->items())
            ->map(fn (GameMaker $maker) => $this->makerToArray($maker))
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
        $maker = GameMaker::query()
            ->with(['synonyms:game_maker_id,synonym'])
            ->find($id);

        if ($maker === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $this->makerToArray($maker),
        ]);
    }

    public function store(MakerRequest $request): JsonResponse
    {
        $maker = new GameMaker();
        $maker->fill($request->validated());
        $maker->synonymsStr = $request->validated('synonymsStr', '');
        $maker->save();

        $maker->load(['synonyms:game_maker_id,synonym']);

        return response()->json([
            'data' => $this->makerToArray($maker),
        ], Response::HTTP_CREATED);
    }

    public function update(MakerRequest $request, int $id): JsonResponse
    {
        $maker = GameMaker::query()->find($id);

        if ($maker === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $maker->fill($request->validated());
        $maker->synonymsStr = $request->validated('synonymsStr', '');
        $maker->save();

        $maker->load(['synonyms:game_maker_id,synonym']);

        return response()->json([
            'data' => $this->makerToArray($maker),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $maker = GameMaker::query()->find($id);

        if ($maker === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $maker->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function packagesIndex(int $id): JsonResponse
    {
        $maker = GameMaker::query()->find($id);

        if ($maker === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $packages = $maker->packages()
            ->orderBy('game_packages.id')
            ->get(['game_packages.id', 'game_packages.name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
            ->values()
            ->all();

        return response()->json(['data' => $packages]);
    }

    public function packagesAttach(Request $request, int $id): JsonResponse
    {
        $maker = GameMaker::query()->find($id);

        if ($maker === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_package_ids' => 'required|array',
            'game_package_ids.*' => 'integer|exists:game_packages,id',
        ]);
        $packageIds = $validated['game_package_ids'] ?? [];
        if ($packageIds !== []) {
            $maker->packages()->syncWithoutDetaching($packageIds);
        }

        return response()->json(['message' => 'OK']);
    }

    public function packagesSync(Request $request, int $id): JsonResponse
    {
        $maker = GameMaker::query()->find($id);

        if ($maker === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_package_ids' => 'nullable|array',
            'game_package_ids.*' => 'integer|exists:game_packages,id',
        ]);
        $packageIds = $validated['game_package_ids'] ?? [];
        $maker->packages()->sync($packageIds);

        return response()->json(['message' => 'OK']);
    }

    public function packagesDetach(int $id, int $packageId): JsonResponse
    {
        $maker = GameMaker::query()->find($id);

        if ($maker === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $maker->packages()->detach([$packageId]);

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
                $sub->where(function (Builder $term) use ($word)
                {
                    $term->where('name', 'LIKE', '%' . $word . '%')
                        ->orWhere('phonetic', 'LIKE', '%' . $word . '%');
                });
            }
        });

        $synonyms = array_map(fn (string $w) => synonym($w), $words);

        $query->orWhereIn('id', function ($sub) use ($synonyms)
        {
            $sub->select('game_maker_id')
                ->from('game_maker_synonyms')
                ->whereIn('synonym', $synonyms);
        });

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function makerToArray(GameMaker $maker): array
    {
        $synonyms = $maker->relationLoaded('synonyms')
            ? $maker->synonyms->pluck('synonym')->values()->all()
            : [];

        return [
            'id' => $maker->id,
            'name' => $maker->name,
            'key' => $maker->key,
            'node_name' => $maker->node_name,
            'rating' => $maker->rating,
            'type' => $maker->type,
            'related_game_maker_id' => $maker->related_game_maker_id,
            'description' => $maker->description,
            'description_source' => $maker->description_source,
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

