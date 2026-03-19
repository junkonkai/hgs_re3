<?php

namespace Tests\Feature\Api\Admin\Game;

use App\Models\GameMaker;
use App\Models\GamePackage;
use App\Models\GamePlatform;

class MakerApiTest extends GameMasterApiTestCase
{
    public function test_index_returns_paginated_list(): void
    {
        GameMaker::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/game/makers?per_page=2');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'key',
                    'node_name',
                    'rating',
                    'type',
                    'related_game_maker_id',
                    'description',
                    'description_source',
                    'synonyms',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
                'from',
                'to',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);
    }

    public function test_index_can_search_by_synonym(): void
    {
        $maker = GameMaker::factory()->create([
            'name' => '株式会社テスト',
            'phonetic' => 'かぶしきがいしゃてすと',
        ]);
        $maker->synonymsStr = "てすと\r\n別名テスト";
        $maker->save();

        $response = $this->getJson('/api/v1/admin/game/makers?q='.urlencode('別名テスト'));

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $maker->id,
            'name' => '株式会社テスト',
        ]);
    }

    public function test_show_returns_maker_with_synonyms(): void
    {
        $maker = GameMaker::factory()->create();
        $maker->synonymsStr = "a\r\nb";
        $maker->save();

        $response = $this->getJson("/api/v1/admin/game/makers/{$maker->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $maker->id);
        $response->assertJsonPath('data.synonyms', [synonym('a'), synonym('b')]);
    }

    public function test_store_creates_maker(): void
    {
        $payload = [
            'name' => 'テストメーカー',
            'key' => 'test-maker-key',
            'node_name' => 'Test Maker',
            'rating' => 0,
            'type' => 1,
            'related_game_maker_id' => null,
            'synonymsStr' => "別名1\r\n別名2",
            'description' => '説明',
            'description_source' => '引用元',
        ];

        $response = $this->postJson('/api/v1/admin/game/makers', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'テストメーカー');
        $response->assertJsonPath('data.synonyms', ['別名1', '別名2']);

        $this->assertDatabaseHas('game_makers', [
            'key' => 'test-maker-key',
            'name' => 'テストメーカー',
        ]);
        $this->assertDatabaseHas('game_maker_synonyms', [
            'synonym' => synonym('別名1'),
        ]);
    }

    public function test_update_updates_maker(): void
    {
        $maker = GameMaker::factory()->create([
            'key' => 'before-key',
            'name' => '変更前',
            'node_name' => 'Before',
            'type' => 1,
            'rating' => 0,
        ]);

        $payload = [
            'name' => '変更後',
            'key' => 'after-key',
            'node_name' => 'After',
            'rating' => 0,
            'type' => 2,
            'synonymsStr' => "x\r\ny",
            'description' => 'desc',
            'description_source' => 'src',
            'related_game_maker_id' => null,
        ];

        $response = $this->putJson("/api/v1/admin/game/makers/{$maker->id}", $payload);

        $response->assertOk();
        $response->assertJsonPath('data.key', 'after-key');
        $response->assertJsonPath('data.synonyms', [synonym('x'), synonym('y')]);

        $this->assertDatabaseHas('game_makers', [
            'id' => $maker->id,
            'key' => 'after-key',
            'name' => '変更後',
        ]);
    }

    public function test_destroy_deletes_maker(): void
    {
        $maker = GameMaker::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/game/makers/{$maker->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('game_makers', [
            'id' => $maker->id,
        ]);
    }

    public function test_packages_crud_attach_sync_detach(): void
    {
        $maker = GameMaker::factory()->create();

        $platform = GamePlatform::query()->create([
            'key' => 'test-platform',
            'game_maker_id' => null,
            'name' => 'Test Platform',
            'acronym' => 'TP',
            'node_name' => 'Test Platform',
            'type' => 1,
            'sort_order' => 0,
            'description' => '',
            'description_source' => null,
        ]);

        $p1 = GamePackage::query()->create([
            'game_platform_id' => $platform->id,
            'name' => 'Package 1',
            'node_name' => 'Package 1',
            'release_at' => '',
        ]);
        $p2 = GamePackage::query()->create([
            'game_platform_id' => $platform->id,
            'name' => 'Package 2',
            'node_name' => 'Package 2',
            'release_at' => '',
        ]);

        $attach = $this->postJson("/api/v1/admin/game/makers/{$maker->id}/packages", [
            'game_package_ids' => [$p1->id, $p2->id],
        ]);
        $attach->assertOk();

        $index = $this->getJson("/api/v1/admin/game/makers/{$maker->id}/packages");
        $index->assertOk();
        $index->assertJsonFragment(['id' => $p1->id, 'name' => 'Package 1']);
        $index->assertJsonFragment(['id' => $p2->id, 'name' => 'Package 2']);

        $sync = $this->putJson("/api/v1/admin/game/makers/{$maker->id}/packages", [
            'game_package_ids' => [$p1->id],
        ]);
        $sync->assertOk();

        $afterSync = $this->getJson("/api/v1/admin/game/makers/{$maker->id}/packages");
        $afterSync->assertOk();
        $afterSync->assertJsonFragment(['id' => $p1->id, 'name' => 'Package 1']);
        $afterSync->assertJsonMissing(['id' => $p2->id, 'name' => 'Package 2']);

        $detach = $this->deleteJson("/api/v1/admin/game/makers/{$maker->id}/packages/{$p1->id}");
        $detach->assertNoContent();

        $afterDetach = $this->getJson("/api/v1/admin/game/makers/{$maker->id}/packages");
        $afterDetach->assertOk();
        $afterDetach->assertJsonMissing(['id' => $p1->id, 'name' => 'Package 1']);
    }
}
