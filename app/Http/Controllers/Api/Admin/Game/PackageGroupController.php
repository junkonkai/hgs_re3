<?php

namespace App\Http\Controllers\Api\Admin\Game;

use App\Http\Controllers\Api\Admin\Game\Concerns\PaginatesAdminGameApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Game\PackageGroupRequest;
use App\Models\GamePackageGroup;
use App\Models\GameTitle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PackageGroupController extends Controller
{
    use PaginatesAdminGameApi;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPageFromRequest($request);
        $q = trim((string) $request->query('q', ''));

        $query = GamePackageGroup::query()->orderByDesc('id');
        $query = $this->applySearchQuery($query, $q);

        $paginator = $query->paginate($perPage)->appends($request->query());

        $data = collect($paginator->items())
            ->map(fn (GamePackageGroup $g) => $this->packageGroupToArray($g))
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
        $group = GamePackageGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->packageGroupToArray($group)]);
    }

    public function store(PackageGroupRequest $request): JsonResponse
    {
        $group = new GamePackageGroup();
        $group->fill($request->validated());
        $group->save();

        return response()->json([
            'data' => $this->packageGroupToArray($group),
        ], Response::HTTP_CREATED);
    }

    public function update(PackageGroupRequest $request, int $id): JsonResponse
    {
        $group = GamePackageGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $group->fill($request->validated());
        $group->save();

        return response()->json(['data' => $this->packageGroupToArray($group)]);
    }

    public function destroy(int $id): JsonResponse
    {
        $group = GamePackageGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $group->packages()->detach();
        $group->titles()->detach();
        $group->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function titlesIndex(int $id): JsonResponse
    {
        $group = GamePackageGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $group->titles()
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
        $group = GamePackageGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        if ($attachOnly) {
            $validated = $request->validate([
                'game_title_ids' => 'required|array',
                'game_title_ids.*' => 'integer|exists:game_titles,id',
            ]);
            $ids = $validated['game_title_ids'] ?? [];
            if ($ids !== []) {
                $group->titles()->syncWithoutDetaching($ids);
            }
        } else {
            $validated = $request->validate([
                'game_title_ids' => 'nullable|array',
                'game_title_ids.*' => 'integer|exists:game_titles,id',
            ]);
            $group->titles()->sync($validated['game_title_ids'] ?? []);
        }

        $this->refreshTitlesLinkedToPackageGroup($group);

        return response()->json(['message' => 'OK']);
    }

    public function titlesDetach(int $id, int $titleId): JsonResponse
    {
        $group = GamePackageGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $group->titles()->detach([$titleId]);

        $this->refreshTitlesLinkedToPackageGroup($group);

        $detachedTitle = GameTitle::query()->find($titleId);
        if ($detachedTitle !== null) {
            $detachedTitle->load('packageGroups.packages');
            $detachedTitle->setFirstReleaseInt()->save();
            $series = $detachedTitle->series;
            if ($series !== null) {
                $series->setTitleParam();
                $series->save();
            }
            $franchise = $detachedTitle->getFranchise();
            if ($franchise !== null) {
                $franchise->setTitleParam();
                $franchise->save();
            }
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function packagesIndex(int $id): JsonResponse
    {
        $group = GamePackageGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $group->packages()
            ->orderBy('game_packages.id')
            ->get(['game_packages.id', 'game_packages.name', 'game_packages.game_platform_id'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'game_platform_id' => $p->game_platform_id,
            ])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function packagesAttach(Request $request, int $id): JsonResponse
    {
        return $this->mutatePackages($request, $id, true);
    }

    public function packagesSync(Request $request, int $id): JsonResponse
    {
        return $this->mutatePackages($request, $id, false);
    }

    private function mutatePackages(Request $request, int $id, bool $attachOnly): JsonResponse
    {
        $group = GamePackageGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        if ($attachOnly) {
            $validated = $request->validate([
                'game_package_ids' => 'required|array',
                'game_package_ids.*' => 'integer|exists:game_packages,id',
            ]);
            $ids = $validated['game_package_ids'] ?? [];
            if ($ids !== []) {
                $group->packages()->syncWithoutDetaching($ids);
            }
        } else {
            $validated = $request->validate([
                'game_package_ids' => 'nullable|array',
                'game_package_ids.*' => 'integer|exists:game_packages,id',
            ]);
            $group->packages()->sync($validated['game_package_ids'] ?? []);
        }

        $group->load('titles');
        $this->refreshTitlesLinkedToPackageGroup($group);

        return response()->json(['message' => 'OK']);
    }

    public function packagesDetach(int $id, int $packageId): JsonResponse
    {
        $group = GamePackageGroup::query()->find($id);

        if ($group === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $group->packages()->detach([$packageId]);

        $group->load('titles');
        $this->refreshTitlesLinkedToPackageGroup($group);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function refreshTitlesLinkedToPackageGroup(GamePackageGroup $group): void
    {
        $group->loadMissing('titles');
        foreach ($group->titles as $title) {
            $title->setFirstReleaseInt()->save();
            $series = $title->series;
            if ($series !== null) {
                $series->setTitleParam();
                $series->save();
            }
            $franchise = $title->getFranchise();
            if ($franchise !== null) {
                $franchise->setTitleParam();
                $franchise->save();
            }
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
    private function packageGroupToArray(GamePackageGroup $g): array
    {
        return [
            'id' => $g->id,
            'name' => $g->name,
            'node_name' => $g->node_name,
            'sort_order' => $g->sort_order,
            'description' => $g->description,
            'description_source' => $g->description_source,
            'simple_shop_text' => $g->simple_shop_text,
        ];
    }
}
