<?php

namespace App\Http\Controllers\Api\Admin\Game;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Game\FranchiseRequest;
use App\Models\GameFranchise;
use App\Models\GameSeries;
use App\Models\GameTitle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FranchiseController extends Controller
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

        $franchises = GameFranchise::query()->orderBy('id');
        $franchises = $this->applySearchQuery($franchises, $q);

        $paginator = $franchises->paginate($perPage)->appends($request->query());

        $data = collect($paginator->items())
            ->map(fn (GameFranchise $f) => $this->franchiseToArray($f))
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
        $franchise = GameFranchise::query()->find($id);

        if ($franchise === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $this->franchiseToArray($franchise),
        ]);
    }

    public function store(FranchiseRequest $request): JsonResponse
    {
        $franchise = new GameFranchise();
        $franchise->fill($request->validated());
        $franchise->setTitleParam();
        $franchise->save();

        return response()->json([
            'data' => $this->franchiseToArray($franchise),
        ], Response::HTTP_CREATED);
    }

    public function update(FranchiseRequest $request, int $id): JsonResponse
    {
        $franchise = GameFranchise::query()->find($id);

        if ($franchise === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $franchise->fill($request->validated());
        $franchise->setTitleParam();
        $franchise->save();

        return response()->json([
            'data' => $this->franchiseToArray($franchise),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $franchise = GameFranchise::query()->find($id);

        if ($franchise === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        try {
            DB::beginTransaction();

            foreach ($franchise->series as $series) {
                $series->game_franchise_id = null;
                $series->save();
            }

            foreach ($franchise->titles as $title) {
                $title->game_franchise_id = null;
                $title->save();
            }

            foreach ($franchise->mediaMixGroups as $mmg) {
                $mmg->game_franchise_id = null;
                $mmg->save();
            }

            foreach ($franchise->mediaMixes as $mm) {
                $mm->game_franchise_id = null;
                $mm->save();
            }

            $franchise->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function seriesIndex(int $id): JsonResponse
    {
        $franchise = GameFranchise::query()->find($id);

        if ($franchise === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $franchise->series()
            ->orderBy('game_series.id')
            ->get(['game_series.id', 'game_series.name'])
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function seriesAttach(Request $request, int $id): JsonResponse
    {
        $franchise = GameFranchise::query()->find($id);

        if ($franchise === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_series_ids' => 'required|array',
            'game_series_ids.*' => 'integer|exists:game_series,id',
        ]);
        $ids = array_values(array_unique($validated['game_series_ids'] ?? []));

        try {
            DB::beginTransaction();

            foreach ($ids as $seriesId) {
                $series = GameSeries::query()->find($seriesId);
                if ($series !== null) {
                    $series->game_franchise_id = $franchise->id;
                    $series->save();
                }
            }

            $franchise->refresh();
            $franchise->load('titles');
            $franchise->setTitleParam();
            $franchise->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json(['message' => 'OK']);
    }

    public function seriesSync(Request $request, int $id): JsonResponse
    {
        $franchise = GameFranchise::query()->find($id);

        if ($franchise === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_series_ids' => 'nullable|array',
            'game_series_ids.*' => 'integer|exists:game_series,id',
        ]);
        $ids = $validated['game_series_ids'] ?? [];

        try {
            DB::beginTransaction();

            foreach ($franchise->series as $series) {
                $series->game_franchise_id = null;
                $series->save();
            }
            foreach ($ids as $seriesId) {
                $series = GameSeries::query()->find($seriesId);
                if ($series !== null) {
                    $series->game_franchise_id = $franchise->id;
                    $series->save();
                }
            }

            $franchise->refresh();
            $franchise->load('titles');
            $franchise->setTitleParam();
            $franchise->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json(['message' => 'OK']);
    }

    public function seriesDetach(int $id, int $seriesId): JsonResponse
    {
        $franchise = GameFranchise::query()->find($id);

        if ($franchise === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $series = GameSeries::query()
            ->where('id', $seriesId)
            ->where('game_franchise_id', $franchise->id)
            ->first();

        if ($series === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $series->game_franchise_id = null;
        $series->save();

        $franchise->refresh();
        $franchise->load('titles');
        $franchise->setTitleParam();
        $franchise->save();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function titlesIndex(int $id): JsonResponse
    {
        $franchise = GameFranchise::query()->find($id);

        if ($franchise === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $franchise->titles()
            ->whereNull('game_series_id')
            ->orderBy('game_titles.id')
            ->get(['game_titles.id', 'game_titles.name'])
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function titlesAttach(Request $request, int $id): JsonResponse
    {
        $franchise = GameFranchise::query()->find($id);

        if ($franchise === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_title_ids' => 'required|array',
            'game_title_ids.*' => 'integer|exists:game_titles,id',
        ]);
        $ids = array_values(array_unique($validated['game_title_ids'] ?? []));

        foreach ($ids as $titleId) {
            $seriesId = GameTitle::query()->whereKey($titleId)->value('game_series_id');
            if ($seriesId !== null) {
                return response()->json([
                    'message' => 'シリーズ未所属のタイトルのみ指定できます。',
                    'errors' => ['game_title_ids' => ['シリーズ未所属のタイトルのみ指定できます。']],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        try {
            DB::beginTransaction();

            foreach ($ids as $titleId) {
                $title = GameTitle::query()->find($titleId);
                if ($title !== null) {
                    $title->game_franchise_id = $franchise->id;
                    $title->save();
                }
            }

            $franchise->refresh();
            $franchise->load('titles');
            $franchise->setTitleParam();
            $franchise->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json(['message' => 'OK']);
    }

    public function titlesSync(Request $request, int $id): JsonResponse
    {
        $franchise = GameFranchise::query()->find($id);

        if ($franchise === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_title_ids' => 'nullable|array',
            'game_title_ids.*' => 'integer|exists:game_titles,id',
        ]);
        $ids = array_values(array_unique($validated['game_title_ids'] ?? []));

        foreach ($ids as $titleId) {
            $seriesId = GameTitle::query()->whereKey($titleId)->value('game_series_id');
            if ($seriesId !== null) {
                return response()->json([
                    'message' => 'シリーズ未所属のタイトルのみ指定できます。',
                    'errors' => ['game_title_ids' => ['シリーズ未所属のタイトルのみ指定できます。']],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        try {
            DB::beginTransaction();

            foreach (GameTitle::query()
                ->where('game_franchise_id', $franchise->id)
                ->whereNull('game_series_id')
                ->get() as $t) {
                $t->game_franchise_id = null;
                $t->save();
            }

            foreach ($ids as $titleId) {
                $title = GameTitle::query()->find($titleId);
                if ($title !== null) {
                    $title->game_franchise_id = $franchise->id;
                    $title->save();
                }
            }

            $franchise->refresh();
            $franchise->load('titles');
            $franchise->setTitleParam();
            $franchise->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json(['message' => 'OK']);
    }

    public function titlesDetach(int $id, int $titleId): JsonResponse
    {
        $franchise = GameFranchise::query()->find($id);

        if ($franchise === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $title = GameTitle::query()
            ->where('id', $titleId)
            ->where('game_franchise_id', $franchise->id)
            ->whereNull('game_series_id')
            ->first();

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $title->game_franchise_id = null;
        $title->save();

        $franchise->refresh();
        $franchise->load('titles');
        $franchise->setTitleParam();
        $franchise->save();

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

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function franchiseToArray(GameFranchise $franchise): array
    {
        return [
            'id' => $franchise->id,
            'name' => $franchise->name,
            'key' => $franchise->key,
            'phonetic' => $franchise->phonetic,
            'node_name' => $franchise->node_name,
            'description' => $franchise->description,
            'description_source' => $franchise->description_source,
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
