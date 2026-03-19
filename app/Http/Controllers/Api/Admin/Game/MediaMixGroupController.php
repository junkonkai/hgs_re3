<?php

namespace App\Http\Controllers\Api\Admin\Game;

use App\Http\Controllers\Api\Admin\Game\Concerns\PaginatesAdminGameApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Game\MediaMixGroupRequest;
use App\Models\GameMediaMix;
use App\Models\GameMediaMixGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MediaMixGroupController extends Controller
{
    use PaginatesAdminGameApi;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPageFromRequest($request);
        $q = trim((string) $request->query('q', ''));

        $query = GameMediaMixGroup::query()->orderByDesc('id');
        $query = $this->applySearchQuery($query, $q);

        $paginator = $query->paginate($perPage)->appends($request->query());

        $data = collect($paginator->items())
            ->map(fn (GameMediaMixGroup $g) => $this->mediaMixGroupToArray($g))
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
        $group = GameMediaMixGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->mediaMixGroupToArray($group)]);
    }

    public function store(MediaMixGroupRequest $request): JsonResponse
    {
        $group = new GameMediaMixGroup();
        $group->fill($request->validated());
        $group->save();

        return response()->json([
            'data' => $this->mediaMixGroupToArray($group),
        ], Response::HTTP_CREATED);
    }

    public function update(MediaMixGroupRequest $request, int $id): JsonResponse
    {
        $group = GameMediaMixGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $group->fill($request->validated());
        $group->save();

        return response()->json(['data' => $this->mediaMixGroupToArray($group)]);
    }

    public function destroy(int $id): JsonResponse
    {
        $group = GameMediaMixGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        foreach ($group->mediaMixes as $mm) {
            $mm->game_media_mix_group_id = null;
            $mm->save();
        }
        $group->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function mediaMixesIndex(int $id): JsonResponse
    {
        $group = GameMediaMixGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $group->mediaMixes()
            ->orderBy('id')
            ->get(['id', 'name', 'key'])
            ->map(fn (GameMediaMix $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'key' => $m->key,
            ])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function mediaMixesAttach(Request $request, int $id): JsonResponse
    {
        $group = GameMediaMixGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_media_mix_ids' => 'required|array',
            'game_media_mix_ids.*' => 'integer|exists:game_media_mixes,id',
        ]);

        foreach ($validated['game_media_mix_ids'] as $mediaMixId) {
            $mm = GameMediaMix::query()->find($mediaMixId);
            if ($mm !== null) {
                $mm->game_franchise_id = null;
                $mm->game_media_mix_group_id = $group->id;
                $mm->save();
            }
        }

        return response()->json(['message' => 'OK']);
    }

    public function mediaMixesSync(Request $request, int $id): JsonResponse
    {
        $group = GameMediaMixGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'game_media_mix_ids' => 'nullable|array',
            'game_media_mix_ids.*' => 'integer|exists:game_media_mixes,id',
        ]);

        foreach ($group->mediaMixes as $mm) {
            $mm->game_media_mix_group_id = null;
            $mm->save();
        }

        foreach ($validated['game_media_mix_ids'] ?? [] as $mediaMixId) {
            $mm = GameMediaMix::query()->find($mediaMixId);
            if ($mm !== null) {
                $mm->game_franchise_id = null;
                $mm->game_media_mix_group_id = $group->id;
                $mm->save();
            }
        }

        return response()->json(['message' => 'OK']);
    }

    public function mediaMixesDetach(int $id, int $mediaMixId): JsonResponse
    {
        $group = GameMediaMixGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $mm = GameMediaMix::query()
            ->where('id', $mediaMixId)
            ->where('game_media_mix_group_id', $group->id)
            ->first();

        if ($mm === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $mm->game_media_mix_group_id = null;
        $mm->save();

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
    private function mediaMixGroupToArray(GameMediaMixGroup $g): array
    {
        return [
            'id' => $g->id,
            'game_franchise_id' => $g->game_franchise_id,
            'name' => $g->name,
            'node_name' => $g->node_name,
            'description' => $g->description,
            'sort_order' => $g->sort_order,
        ];
    }
}
