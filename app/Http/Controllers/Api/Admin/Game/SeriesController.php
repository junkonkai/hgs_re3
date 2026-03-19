<?php

namespace App\Http\Controllers\Api\Admin\Game;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Game\SeriesRequest;
use App\Models\GameSeries;
use App\Models\GameTitle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SeriesController extends Controller
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

        $series = GameSeries::query()->orderBy('id');
        $series = $this->applySearchQuery($series, $q);

        $paginator = $series->paginate($perPage)->appends($request->query());

        $data = collect($paginator->items())
            ->map(fn (GameSeries $s) => $this->seriesToArray($s))
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
        $series = GameSeries::query()->find($id);

        if ($series === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->seriesToArray($series)]);
    }

    public function store(SeriesRequest $request): JsonResponse
    {
        $series = new GameSeries();
        $series->fill($request->validated());
        $series->save();

        return response()->json([
            'data' => $this->seriesToArray($series),
        ], Response::HTTP_CREATED);
    }

    public function update(SeriesRequest $request, int $id): JsonResponse
    {
        $series = GameSeries::query()->find($id);

        if ($series === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $oldFranchise = $series->franchise;
        $series->fill($request->validated());
        $series->save();

        $franchise = $series->franchise;
        if ($franchise !== null) {
            $franchise->load('titles');
            $franchise->setTitleParam();
            $franchise->save();
        }

        if ($oldFranchise !== null && ($franchise === null || $oldFranchise->id !== $franchise->id)) {
            $oldFranchise->load('titles');
            $oldFranchise->setTitleParam();
            $oldFranchise->save();
        }

        return response()->json(['data' => $this->seriesToArray($series->fresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        $series = GameSeries::query()->find($id);

        if ($series === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $oldFranchise = $series->franchise;

        foreach ($series->titles as $title) {
            $title->game_series_id = null;
            $title->save();
        }
        $series->delete();

        if ($oldFranchise !== null) {
            $oldFranchise->load('titles');
            $oldFranchise->setTitleParam();
            $oldFranchise->save();
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function titlesIndex(int $id): JsonResponse
    {
        $series = GameSeries::query()->find($id);

        if ($series === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $series->titles()
            ->orderBy('game_titles.id')
            ->get(['game_titles.id', 'game_titles.name'])
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function titlesAttach(Request $request, int $id): JsonResponse
    {
        $series = GameSeries::query()->find($id);

        if ($series === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_title_ids' => 'required|array',
            'game_title_ids.*' => 'integer|exists:game_titles,id',
        ]);
        $ids = $validated['game_title_ids'] ?? [];

        try {
            DB::beginTransaction();

            foreach ($ids as $titleId) {
                $title = GameTitle::query()->find($titleId);
                if ($title !== null) {
                    $title->game_franchise_id = null;
                    $title->game_series_id = $series->id;
                    $title->save();
                }
            }

            $franchise = $series->franchise;
            if ($franchise !== null) {
                $franchise->load('titles');
                $franchise->setTitleParam();
                $franchise->save();
            }
            $series->load('titles');
            $series->setTitleParam();
            $series->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json(['message' => 'OK']);
    }

    public function titlesSync(Request $request, int $id): JsonResponse
    {
        $series = GameSeries::query()->find($id);

        if ($series === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_title_ids' => 'nullable|array',
            'game_title_ids.*' => 'integer|exists:game_titles,id',
        ]);
        $ids = $validated['game_title_ids'] ?? [];

        try {
            DB::beginTransaction();

            foreach ($series->titles as $title) {
                $title->game_series_id = null;
                $title->save();
            }
            foreach ($ids as $titleId) {
                $title = GameTitle::query()->find($titleId);
                if ($title !== null) {
                    $title->game_franchise_id = null;
                    $title->game_series_id = $series->id;
                    $title->save();
                }
            }

            $franchise = $series->franchise;
            if ($franchise !== null) {
                $franchise->load('titles');
                $franchise->setTitleParam();
                $franchise->save();
            }
            $series->load('titles');
            $series->setTitleParam();
            $series->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json(['message' => 'OK']);
    }

    public function titlesDetach(int $id, int $titleId): JsonResponse
    {
        $series = GameSeries::query()->find($id);

        if ($series === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $title = GameTitle::query()
            ->where('id', $titleId)
            ->where('game_series_id', $series->id)
            ->first();

        if ($title === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $title->game_series_id = null;
        $title->save();

        $franchise = $series->franchise;
        if ($franchise !== null) {
            $franchise->load('titles');
            $franchise->setTitleParam();
            $franchise->save();
        }
        $series->load('titles');
        $series->setTitleParam();
        $series->save();

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
    private function seriesToArray(GameSeries $series): array
    {
        return [
            'id' => $series->id,
            'game_franchise_id' => $series->game_franchise_id,
            'name' => $series->name,
            'phonetic' => $series->phonetic,
            'node_name' => $series->node_name,
            'description' => $series->description,
            'description_source' => $series->description_source,
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
