<?php

namespace Tests\Feature\Api\Admin\Game;

use App\Enums\Shop;
use App\Models\GameMaker;
use App\Models\GamePackageGroup;
use App\Models\GamePlatform;

class PackageApiTest extends GameMasterApiTestCase
{
    private function createPlatform(): GamePlatform
    {
        return GamePlatform::query()->create([
            'key' => 'p-pkg-'.uniqid('', true),
            'game_maker_id' => null,
            'name' => 'PlatX',
            'acronym' => 'PX',
            'node_name' => 'PX',
            'type' => 1,
            'sort_order' => 0,
            'description' => '',
            'description_source' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function packagePayload(int $platformId, array $overrides = []): array
    {
        return array_merge([
            'name' => 'パッケージ名',
            'acronym' => null,
            'node_name' => 'NN',
            'game_platform_id' => $platformId,
            'release_at' => '2021-06-15',
            'sort_order' => 10,
            'default_img_type' => 1,
            'rating' => 0,
        ], $overrides);
    }

    public function test_crud_makers_package_groups_shops(): void
    {
        $plat = $this->createPlatform();
        $maker = GameMaker::factory()->create();

        $store = $this->postJson('/api/v1/admin/game/packages', $this->packagePayload($plat->id, [
            'game_maker_ids' => [$maker->id],
        ]));
        $store->assertCreated();
        $pid = $store->json('data.id');
        $store->assertJsonPath('data.makers.0.id', $maker->id);

        $this->getJson('/api/v1/admin/game/packages?q='.urlencode('パッケージ'))
            ->assertOk()
            ->assertJsonFragment(['id' => $pid]);

        $this->getJson("/api/v1/admin/game/packages/{$pid}")->assertOk()
            ->assertJsonPath('data.game_platform_id', $plat->id);

        $g = GamePackageGroup::query()->create([
            'name' => 'G',
            'node_name' => 'G',
            'sort_order' => 0,
            'description' => null,
            'description_source' => null,
            'simple_shop_text' => null,
        ]);

        $this->postJson("/api/v1/admin/game/packages/{$pid}/package-groups", [
            'game_package_group_ids' => [$g->id],
        ])->assertOk();

        $this->getJson("/api/v1/admin/game/packages/{$pid}/package-groups")
            ->assertOk()
            ->assertJsonFragment(['id' => $g->id]);

        $this->putJson("/api/v1/admin/game/packages/{$pid}", $this->packagePayload($plat->id, [
            'name' => '更新パッケージ',
        ]))->assertOk()->assertJsonPath('data.name', '更新パッケージ');

        $shopRes = $this->postJson("/api/v1/admin/game/packages/{$pid}/shops", [
            'shop_id' => Shop::Amazon->value,
            'url' => 'https://example.com/p',
            'img_tag' => null,
            'param1' => null,
            'param2' => null,
            'param3' => null,
        ]);
        $shopRes->assertCreated();
        $shopId = $shopRes->json('data.id');

        $this->getJson("/api/v1/admin/game/packages/{$pid}/shops/{$shopId}")->assertOk();
        $this->putJson("/api/v1/admin/game/packages/{$pid}/shops/{$shopId}", [
            'shop_id' => Shop::Amazon->value,
            'url' => 'https://example.com/p2',
        ])->assertOk()->assertJsonPath('data.url', 'https://example.com/p2');

        $this->deleteJson("/api/v1/admin/game/packages/{$pid}/shops/{$shopId}")->assertNoContent();

        $this->deleteJson("/api/v1/admin/game/packages/{$pid}")->assertNoContent();
    }

    public function test_platform_ids_filter_on_index(): void
    {
        $p1 = $this->createPlatform();
        $p2 = GamePlatform::query()->create([
            'key' => 'p2-'.uniqid('', true),
            'game_maker_id' => null,
            'name' => 'P2',
            'acronym' => 'P2',
            'node_name' => 'P2',
            'type' => 1,
            'sort_order' => 0,
            'description' => '',
            'description_source' => null,
        ]);

        $this->postJson('/api/v1/admin/game/packages', $this->packagePayload($p1->id))->assertCreated();
        $r2 = $this->postJson('/api/v1/admin/game/packages', $this->packagePayload($p2->id));
        $r2->assertCreated();
        $id2 = $r2->json('data.id');

        $this->getJson('/api/v1/admin/game/packages?platform_ids[]='.$p2->id)
            ->assertOk()
            ->assertJsonFragment(['id' => $id2]);
    }
}
