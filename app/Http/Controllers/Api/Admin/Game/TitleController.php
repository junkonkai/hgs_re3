<?php

namespace App\Http\Controllers\Api\Admin\Game;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Game\TitleRequest;
use App\Models\GameTitle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TitleController extends Controller
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

        $titles = GameTitle::query()->orderBy('id');
        $titles = $this->applySearchQuery($titles, $q);

        $paginator = $titles->paginate($perPage)->appends($request->query());

        $data = collect($paginator->items())
            ->map(fn (GameTitle $t) => $this->titleToArray($t))
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
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->titleToArray($title)]);
    }

    public function store(TitleRequest $request): JsonResponse
    {
        $title = new GameTitle();
        $this->fillTitleFromValidated($title, $request->validated());
        $title->setOgpInfo($request->input('ogp_url'));
        $title->save();

        $this->refreshFranchiseAndSeriesParams($title);

        return response()->json([
            'data' => $this->titleToArray($title->fresh()),
        ], Response::HTTP_CREATED);
    }

    public function update(TitleRequest $request, int $id): JsonResponse
    {
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $this->fillTitleFromValidated($title, $request->validated());
        $title->setOgpInfo($request->input('ogp_url'));
        $title->save();

        $this->refreshFranchiseAndSeriesParams($title);

        return response()->json(['data' => $this->titleToArray($title->fresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $franchise = $title->getFranchise();
        $series = $title->series;

        $title->packageGroups()->detach();
        $title->relatedProducts()->detach();
        $title->mediaMixes()->detach();
        $title->delete();

        if ($franchise !== null) {
            $franchise->load('titles');
            $franchise->setTitleParam();
            $franchise->save();
        }
        if ($series !== null) {
            $series->load('titles');
            $series->setTitleParam();
            $series->save();
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function packageGroupsIndex(int $id): JsonResponse
    {
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $title->packageGroups()
            ->orderBy('game_package_groups.id')
            ->get(['game_package_groups.id', 'game_package_groups.name'])
            ->map(fn ($g) => ['id' => $g->id, 'name' => $g->name])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function packageGroupsAttach(Request $request, int $id): JsonResponse
    {
        return $this->mutatePackageGroups($request, $id, true);
    }

    public function packageGroupsSync(Request $request, int $id): JsonResponse
    {
        return $this->mutatePackageGroups($request, $id, false);
    }

    private function mutatePackageGroups(Request $request, int $id, bool $attachOnly): JsonResponse
    {
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        if ($attachOnly) {
            $validated = $request->validate([
                'game_package_group_ids' => 'required|array',
                'game_package_group_ids.*' => 'integer|exists:game_package_groups,id',
            ]);
            $ids = $validated['game_package_group_ids'] ?? [];
            if ($ids !== []) {
                $title->packageGroups()->syncWithoutDetaching($ids);
            }
        } else {
            $validated = $request->validate([
                'game_package_group_ids' => 'nullable|array',
                'game_package_group_ids.*' => 'integer|exists:game_package_groups,id',
            ]);
            $title->packageGroups()->sync($validated['game_package_group_ids'] ?? []);
        }

        $title->load('packageGroups.packages');
        $title->setFirstReleaseInt();
        $title->save();

        $series = $title->series;
        if ($series !== null) {
            $series->load('titles');
            $series->setTitleParam();
            $series->save();
        }

        return response()->json(['message' => 'OK']);
    }

    public function packageGroupsDetach(int $id, int $packageGroupId): JsonResponse
    {
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $title->packageGroups()->detach([$packageGroupId]);

        $title->load('packageGroups.packages');
        $title->setFirstReleaseInt();
        $title->save();

        $series = $title->series;
        if ($series !== null) {
            $series->load('titles');
            $series->setTitleParam();
            $series->save();
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function relatedProductsIndex(int $id): JsonResponse
    {
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $title->relatedProducts()
            ->orderBy('game_related_products.id')
            ->get(['game_related_products.id', 'game_related_products.name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function relatedProductsAttach(Request $request, int $id): JsonResponse
    {
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_related_product_ids' => 'required|array',
            'game_related_product_ids.*' => 'integer|exists:game_related_products,id',
        ]);
        $ids = $validated['game_related_product_ids'] ?? [];
        if ($ids !== []) {
            $title->relatedProducts()->syncWithoutDetaching($ids);
        }

        return response()->json(['message' => 'OK']);
    }

    public function relatedProductsSync(Request $request, int $id): JsonResponse
    {
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_related_product_ids' => 'nullable|array',
            'game_related_product_ids.*' => 'integer|exists:game_related_products,id',
        ]);
        $title->relatedProducts()->sync($validated['game_related_product_ids'] ?? []);

        return response()->json(['message' => 'OK']);
    }

    public function relatedProductsDetach(int $id, int $relatedProductId): JsonResponse
    {
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $title->relatedProducts()->detach([$relatedProductId]);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function mediaMixesIndex(int $id): JsonResponse
    {
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $title->mediaMixes()
            ->orderBy('game_media_mixes.id')
            ->get(['game_media_mixes.id', 'game_media_mixes.name'])
            ->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function mediaMixesAttach(Request $request, int $id): JsonResponse
    {
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_media_mix_ids' => 'required|array',
            'game_media_mix_ids.*' => 'integer|exists:game_media_mixes,id',
        ]);
        $ids = $validated['game_media_mix_ids'] ?? [];
        if ($ids !== []) {
            $title->mediaMixes()->syncWithoutDetaching($ids);
        }

        return response()->json(['message' => 'OK']);
    }

    public function mediaMixesSync(Request $request, int $id): JsonResponse
    {
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_media_mix_ids' => 'nullable|array',
            'game_media_mix_ids.*' => 'integer|exists:game_media_mixes,id',
        ]);
        $title->mediaMixes()->sync($validated['game_media_mix_ids'] ?? []);

        return response()->json(['message' => 'OK']);
    }

    public function mediaMixesDetach(int $id, int $mediaMixId): JsonResponse
    {
        $title = GameTitle::query()->find($id);

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $title->mediaMixes()->detach([$mediaMixId]);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function fillTitleFromValidated(GameTitle $title, array $validated): void
    {
        $data = $validated;
        if (array_key_exists('original_package_id', $data)) {
            $title->original_game_package_id = $data['original_package_id'];
            unset($data['original_package_id']);
        }
        $title->fill($data);
    }

    private function refreshFranchiseAndSeriesParams(GameTitle $title): void
    {
        $franchise = $title->getFranchise();
        if ($franchise !== null) {
            $franchise->load('titles');
            $franchise->setTitleParam();
            $franchise->save();
        }

        $series = $title->series;
        if ($series !== null) {
            $series->load('titles');
            $series->setTitleParam();
            $series->save();
        }
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

        $query->where(function (Builder $outer) use ($words)
        {
            $outer->where(function (Builder $sub) use ($words)
            {
                foreach ($words as $word) {
                    $sub->where(function (Builder $term) use ($word)
                    {
                        $term->where('name', 'LIKE', '%' . $word . '%')
                            ->orWhere('phonetic', 'LIKE', '%' . $word . '%');
                    });
                }
            });

            $synonymWords = array_map(fn (string $w) => synonym($w), $words);
            $outer->orWhere(function (Builder $syn) use ($synonymWords)
            {
                foreach ($synonymWords as $sw) {
                    $syn->orWhere('search_synonyms', 'LIKE', '%' . $sw . '%');
                }
            });
        });

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function titleToArray(GameTitle $title): array
    {
        $rating = $title->rating;
        if ($rating instanceof \BackedEnum) {
            $rating = $rating->value;
        }

        return [
            'id' => $title->id,
            'game_franchise_id' => $title->game_franchise_id,
            'game_series_id' => $title->game_series_id,
            'name' => $title->name,
            'key' => $title->key,
            'phonetic' => $title->phonetic,
            'node_name' => $title->node_name,
            'description' => $title->description ?? '',
            'first_release_int' => (int) $title->first_release_int,
            'rating' => $rating,
            'use_ogp_description' => (bool) $title->use_ogp_description,
            'search_synonyms' => $title->search_synonyms,
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
