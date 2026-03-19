<?php

namespace Tests\Feature\Api\Admin\Game;

use App\Models\GamePlatform;
use App\Models\GameRelatedProduct;

class PlatformApiTest extends GameMasterApiTestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPlatform(array $overrides = []): GamePlatform
    {
        $key = $overrides['key'] ?? 'plat-'.uniqid('', true);

        return GamePlatform::query()->create(array_merge([
            'key' => $key,
            'game_maker_id' => null,
            'name' => 'Test Platform Name',
            'acronym' => 'TP',
            'node_name' => 'Test Platform',
            'type' => 1,
            'sort_order' => 0,
            'description' => '',
            'description_source' => null,
        ], $overrides));
    }

    public function test_index_returns_paginated_list(): void
    {
        $this->createPlatform();
        $this->createPlatform();
        $this->createPlatform();

        $response = $this->getJson('/api/v1/admin/game/platforms?per_page=2');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'key',
                    'acronym',
                    'node_name',
                    'type',
                    'sort_order',
                    'game_maker_id',
                    'description',
                    'description_source',
                    'synonyms',
                ],
            ],
            'meta',
            'links',
        ]);
    }

    public function test_index_can_search_by_synonym(): void
    {
        $platform = $this->createPlatform([
            'name' => 'ユニークプラットフォーム名XYZ',
        ]);
        $platform->synonymsStr = "検索用シノニム\r\n別行";
        $platform->save();

        $response = $this->getJson('/api/v1/admin/game/platforms?q='.urlencode('検索用シノニム'));

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $platform->id,
            'name' => 'ユニークプラットフォーム名XYZ',
        ]);
    }

    public function test_show_returns_platform_with_synonyms(): void
    {
        $platform = $this->createPlatform();
        $platform->synonymsStr = "a\r\nb";
        $platform->save();

        $response = $this->getJson("/api/v1/admin/game/platforms/{$platform->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $platform->id);
        $response->assertJsonPath('data.synonyms', [synonym('a'), synonym('b')]);
    }

    public function test_store_creates_platform(): void
    {
        $key = 'api-plat-'.uniqid('', true);
        $payload = [
            'name' => '新規PF',
            'key' => $key,
            'acronym' => 'NP',
            'node_name' => 'New PF',
            'type' => 1,
            'sort_order' => 10,
            'game_maker_id' => null,
            'synonymsStr' => "俗称1\r\n俗称2",
            'description' => '説明',
            'description_source' => '出典',
        ];

        $response = $this->postJson('/api/v1/admin/game/platforms', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.name', '新規PF');
        $response->assertJsonPath('data.synonyms', [
            synonym('俗称1'),
            synonym('俗称2'),
        ]);

        $this->assertDatabaseHas('game_platforms', [
            'key' => $key,
            'name' => '新規PF',
        ]);
    }

    public function test_update_updates_platform(): void
    {
        $platform = $this->createPlatform([
            'key' => 'before-plat-'.uniqid('', true),
            'name' => '変更前',
        ]);

        $newKey = 'after-plat-'.uniqid('', true);
        $payload = [
            'name' => '変更後',
            'key' => $newKey,
            'acronym' => 'AF',
            'node_name' => 'After',
            'type' => 2,
            'sort_order' => 5,
            'game_maker_id' => null,
            'synonymsStr' => "x\r\ny",
            'description' => 'd',
            'description_source' => null,
        ];

        $response = $this->putJson("/api/v1/admin/game/platforms/{$platform->id}", $payload);

        $response->assertOk();
        $response->assertJsonPath('data.key', $newKey);
        $response->assertJsonPath('data.synonyms', [synonym('x'), synonym('y')]);

        $this->assertDatabaseHas('game_platforms', [
            'id' => $platform->id,
            'key' => $newKey,
            'name' => '変更後',
        ]);
    }

    public function test_destroy_deletes_platform(): void
    {
        $platform = $this->createPlatform();

        $response = $this->deleteJson("/api/v1/admin/game/platforms/{$platform->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('game_platforms', [
            'id' => $platform->id,
        ]);
    }

    public function test_related_products_attach_sync_detach(): void
    {
        $platform = $this->createPlatform();

        $rp1 = GameRelatedProduct::query()->create([
            'name' => '関連商品1',
            'node_name' => '関連商品1',
            'description' => '',
        ]);
        $rp2 = GameRelatedProduct::query()->create([
            'name' => '関連商品2',
            'node_name' => '関連商品2',
            'description' => '',
        ]);

        $attach = $this->postJson("/api/v1/admin/game/platforms/{$platform->id}/related-products", [
            'game_related_product_ids' => [$rp1->id, $rp2->id],
        ]);
        $attach->assertOk();

        $index = $this->getJson("/api/v1/admin/game/platforms/{$platform->id}/related-products");
        $index->assertOk();
        $index->assertJsonFragment(['id' => $rp1->id, 'name' => '関連商品1']);
        $index->assertJsonFragment(['id' => $rp2->id, 'name' => '関連商品2']);

        $sync = $this->putJson("/api/v1/admin/game/platforms/{$platform->id}/related-products", [
            'game_related_product_ids' => [$rp1->id],
        ]);
        $sync->assertOk();

        $afterSync = $this->getJson("/api/v1/admin/game/platforms/{$platform->id}/related-products");
        $afterSync->assertOk();
        $afterSync->assertJsonFragment(['id' => $rp1->id, 'name' => '関連商品1']);
        $afterSync->assertJsonMissing(['id' => $rp2->id, 'name' => '関連商品2']);

        $detach = $this->deleteJson("/api/v1/admin/game/platforms/{$platform->id}/related-products/{$rp1->id}");
        $detach->assertNoContent();

        $afterDetach = $this->getJson("/api/v1/admin/game/platforms/{$platform->id}/related-products");
        $afterDetach->assertOk();
        $afterDetach->assertJsonMissing(['id' => $rp1->id, 'name' => '関連商品1']);
    }
}
