<?php

namespace App\Http\Controllers\Api\Admin\Game;

use App\Http\Controllers\Api\Admin\Game\Concerns\PaginatesAdminGameApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Game\PackageRequest;
use App\Http\Requests\Api\Admin\Game\PackageShopRequest;
use App\Models\GamePackage;
use App\Models\GamePackageShop;
use App\Models\GameTitlePackageLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PackageController extends Controller
{
    use PaginatesAdminGameApi;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->perPageFromRequest($request);
        $q = trim((string) $request->query('q', ''));
        $platformIds = array_values(array_filter(
            array_map('intval', (array) $request->query('platform_ids', []))
        ));

        $query = GamePackage::query()->orderByDesc('id');
        $query = $this->applyPackageSearch($query, $q, $platformIds);

        $paginator = $query->paginate($perPage)->appends($request->query());

        $data = collect($paginator->items())
            ->map(fn (GamePackage $p) => $this->packageToArray($p))
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
        $package = GamePackage::query()
            ->with(['platform:id,acronym,name', 'makers:id,name'])
            ->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->packageToArray($package, true)]);
    }

    public function store(PackageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $makerIds = $validated['game_maker_ids'] ?? [];
        unset($validated['game_maker_ids']);

        $package = new GamePackage();
        $package->fill($validated);
        $package->save();

        if ($makerIds !== []) {
            $package->makers()->sync($makerIds);
        }

        foreach ($package->makers as $maker) {
            $maker->setRating()->save();
        }

        $package->load(['platform:id,acronym,name', 'makers:id,name']);

        return response()->json([
            'data' => $this->packageToArray($package, true),
        ], Response::HTTP_CREATED);
    }

    public function update(PackageRequest $request, int $id): JsonResponse
    {
        $package = GamePackage::query()->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validated();
        $makerIds = $request->has('game_maker_ids')
            ? ($validated['game_maker_ids'] ?? [])
            : null;
        unset($validated['game_maker_ids']);

        $package->fill($validated);
        $package->save();

        if ($makerIds !== null) {
            $package->makers()->sync($makerIds);
        }

        $this->refreshTitlesViaPackageGroups($package);

        foreach ($package->makers as $maker) {
            $maker->setRating()->save();
        }

        $package->load(['platform:id,acronym,name', 'makers:id,name']);

        return response()->json(['data' => $this->packageToArray($package, true)]);
    }

    public function destroy(int $id): JsonResponse
    {
        $package = GamePackage::query()
            ->with(['makers', 'packageGroups.titles'])
            ->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $titles = [];
        foreach ($package->packageGroups as $pg) {
            foreach ($pg->titles as $title) {
                $titles[$title->id] = $title;
            }
        }

        $makers = $package->makers;
        $package->delete();

        foreach ($makers as $maker) {
            $maker->setRating()->save();
        }

        foreach ($titles as $title) {
            $title->setFirstReleaseInt()->save();
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function makersIndex(int $id): JsonResponse
    {
        $package = GamePackage::query()->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $package->makers()
            ->orderBy('game_makers.id')
            ->get(['game_makers.id', 'game_makers.name'])
            ->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function makersAttach(Request $request, int $id): JsonResponse
    {
        return $this->mutateMakers($request, $id, true);
    }

    public function makersSync(Request $request, int $id): JsonResponse
    {
        return $this->mutateMakers($request, $id, false);
    }

    private function mutateMakers(Request $request, int $id, bool $attachOnly): JsonResponse
    {
        $package = GamePackage::query()->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        if ($attachOnly) {
            $validated = $request->validate([
                'game_maker_ids' => 'required|array',
                'game_maker_ids.*' => 'integer|exists:game_makers,id',
            ]);
            $ids = $validated['game_maker_ids'] ?? [];
            if ($ids !== []) {
                $package->makers()->syncWithoutDetaching($ids);
            }
        } else {
            $validated = $request->validate([
                'game_maker_ids' => 'nullable|array',
                'game_maker_ids.*' => 'integer|exists:game_makers,id',
            ]);
            $package->makers()->sync($validated['game_maker_ids'] ?? []);
        }

        foreach ($package->makers as $maker) {
            $maker->setRating()->save();
        }

        return response()->json(['message' => 'OK']);
    }

    public function makersDetach(int $id, int $makerId): JsonResponse
    {
        $package = GamePackage::query()->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $package->makers()->detach([$makerId]);

        foreach ($package->makers as $maker) {
            $maker->setRating()->save();
        }

        $detached = \App\Models\GameMaker::query()->find($makerId);
        if ($detached !== null) {
            $detached->setRating()->save();
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function packageGroupsIndex(int $id): JsonResponse
    {
        $package = GamePackage::query()->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $package->packageGroups()
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
        $package = GamePackage::query()->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        if ($attachOnly) {
            $validated = $request->validate([
                'game_package_group_ids' => 'required|array',
                'game_package_group_ids.*' => 'integer|exists:game_package_groups,id',
            ]);
            $ids = $validated['game_package_group_ids'] ?? [];
            if ($ids !== []) {
                $package->packageGroups()->syncWithoutDetaching($ids);
            }
        } else {
            $validated = $request->validate([
                'game_package_group_ids' => 'nullable|array',
                'game_package_group_ids.*' => 'integer|exists:game_package_groups,id',
            ]);
            $package->packageGroups()->sync($validated['game_package_group_ids'] ?? []);
        }

        $this->refreshTitlesViaPackageGroups($package->fresh(['packageGroups.titles']));

        return response()->json(['message' => 'OK']);
    }

    public function packageGroupsDetach(int $id, int $packageGroupId): JsonResponse
    {
        $package = GamePackage::query()->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $package->packageGroups()->detach([$packageGroupId]);

        $this->refreshTitlesViaPackageGroups($package->fresh(['packageGroups.titles']));

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function shopsIndex(int $id): JsonResponse
    {
        $package = GamePackage::query()->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $rows = $package->shops()
            ->orderBy('id')
            ->get()
            ->map(fn (GamePackageShop $s) => $this->packageShopToArray($s))
            ->values()
            ->all();

        return response()->json(['data' => $rows]);
    }

    public function shopsShow(int $id, int $shopId): JsonResponse
    {
        $package = GamePackage::query()->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $shop = $package->shops()->where('id', $shopId)->first();

        if ($shop === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['data' => $this->packageShopToArray($shop)]);
    }

    public function shopsStore(PackageShopRequest $request, int $id): JsonResponse
    {
        $package = GamePackage::query()->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $shop = new GamePackageShop();
        $shop->game_package_id = $package->id;
        $shop->fill($request->validated());
        $shop->setOgpInfo($request->input('ogp_url'));
        $shop->save();

        return response()->json([
            'data' => $this->packageShopToArray($shop),
        ], Response::HTTP_CREATED);
    }

    public function shopsUpdate(PackageShopRequest $request, int $id, int $shopId): JsonResponse
    {
        $package = GamePackage::query()->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $shop = $package->shops()->where('id', $shopId)->first();

        if ($shop === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $shop->fill($request->validated());
        $shop->setOgpInfo($request->input('ogp_url'));
        $shop->save();

        return response()->json(['data' => $this->packageShopToArray($shop)]);
    }

    public function shopsDestroy(int $id, int $shopId): JsonResponse
    {
        $package = GamePackage::query()->find($id);

        if ($package === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $shop = $package->shops()->where('id', $shopId)->first();

        if ($shop === null) {
            return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
        }

        $shop->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function refreshTitlesViaPackageGroups(GamePackage $package): void
    {
        $package->loadMissing('packageGroups.titles');
        $seen = [];
        foreach ($package->packageGroups as $pg) {
            foreach ($pg->titles as $title) {
                if (isset($seen[$title->id])) {
                    continue;
                }
                $seen[$title->id] = true;
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
    }

    /**
     * @param array<int> $platformIds
     */
    private function applyPackageSearch(Builder $query, string $q, array $platformIds): Builder
    {
        if ($q !== '') {
            $words = array_values(array_filter(preg_split('/\s+/u', $q) ?: [], fn ($w) => $w !== ''));

            if ($words !== []) {
                $query->where(function (Builder $sub) use ($words)
                {
                    $sub->where(function (Builder $nameQ) use ($words)
                    {
                        foreach ($words as $word) {
                            $nameQ->where('name', 'LIKE', '%' . $word . '%');
                        }
                    });

                    $synonymWords = array_map(fn (string $w) => synonym($w), $words);
                    $pkgIds = GameTitlePackageLink::query()
                        ->whereIn('game_title_id', function ($q2) use ($synonymWords)
                        {
                            $q2->select('game_title_id')
                                ->from('game_title_synonyms')
                                ->whereIn('synonym', $synonymWords);
                        })
                        ->pluck('game_package_id')
                        ->unique()
                        ->filter()
                        ->values()
                        ->all();

                    if ($pkgIds !== []) {
                        $sub->orWhereIn('id', $pkgIds);
                    }
                });
            }
        }

        if ($platformIds !== []) {
            $query->whereIn('game_platform_id', $platformIds);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function packageToArray(GamePackage $p, bool $detail = false): array
    {
        $base = [
            'id' => $p->id,
            'name' => $p->name,
            'acronym' => $p->acronym,
            'node_name' => $p->node_name,
            'game_platform_id' => $p->game_platform_id,
            'release_at' => $p->release_at,
            'sort_order' => $p->sort_order,
            'default_img_type' => $p->default_img_type instanceof \BackedEnum
                ? $p->default_img_type->value
                : $p->default_img_type,
            'rating' => $p->rating instanceof \BackedEnum ? $p->rating->value : $p->rating,
        ];

        if ($detail) {
            $base['platform'] = $p->relationLoaded('platform') && $p->platform
                ? [
                    'id' => $p->platform->id,
                    'name' => $p->platform->name,
                    'acronym' => $p->platform->acronym,
                ]
                : null;
            $base['makers'] = $p->relationLoaded('makers')
                ? $p->makers->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values()->all()
                : [];
        }

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function packageShopToArray(GamePackageShop $s): array
    {
        return [
            'id' => $s->id,
            'game_package_id' => $s->game_package_id,
            'shop_id' => $s->shop_id,
            'url' => $s->url,
            'img_tag' => $s->img_tag,
            'param1' => $s->param1,
            'param2' => $s->param2,
            'param3' => $s->param3,
        ];
    }
}
