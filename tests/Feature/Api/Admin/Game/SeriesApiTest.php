<?php

namespace Tests\Feature\Api\Admin\Game;

use App\Models\GameFranchise;
use App\Models\GameTitle;

class SeriesApiTest extends GameMasterApiTestCase
{
    private function createFranchise(): GameFranchise
    {
        return GameFranchise::query()->create([
            'key' => 'sf-'.uniqid('', true),
            'name' => 'F',
            'phonetic' => 'えふ',
            'node_name' => 'F',
            'description' => null,
            'description_source' => null,
        ]);
    }

    private function createOrphanTitle(): GameTitle
    {
        return GameTitle::query()->create([
            'key' => 'st-'.uniqid('', true),
            'game_franchise_id' => null,
            'game_series_id' => null,
            'name' => '孤児タイトル',
            'phonetic' => 'こじたいとる',
            'node_name' => 'OT',
            'first_release_int' => 0,
            'rating' => 0,
            'use_ogp_description' => 0,
            'search_synonyms' => '',
            'description' => '',
        ]);
    }

    public function test_crud_and_titles_sync_attach_detach(): void
    {
        $f = $this->createFranchise();

        $this->getJson('/api/v1/admin/game/series?per_page=5')->assertOk();

        $store = $this->postJson('/api/v1/admin/game/series', [
            'game_franchise_id' => $f->id,
            'name' => 'テストシリーズ',
            'phonetic' => 'てすとしりーず',
            'node_name' => 'TS',
            'description' => null,
            'description_source' => null,
        ]);
        $store->assertCreated();
        $sid = $store->json('data.id');

        $this->getJson("/api/v1/admin/game/series/{$sid}")->assertOk();

        $this->putJson("/api/v1/admin/game/series/{$sid}", [
            'game_franchise_id' => $f->id,
            'name' => '更新シリーズ',
            'phonetic' => 'こうしんしりーず',
            'node_name' => 'US',
            'description' => 'd',
            'description_source' => null,
        ])->assertOk();

        $t1 = $this->createOrphanTitle();
        $t2 = $this->createOrphanTitle();

        $this->putJson("/api/v1/admin/game/series/{$sid}/titles", [
            'game_title_ids' => [$t1->id, $t2->id],
        ])->assertOk();

        $this->getJson("/api/v1/admin/game/series/{$sid}/titles")
            ->assertOk()
            ->assertJsonFragment(['id' => $t1->id]);

        $t3 = $this->createOrphanTitle();
        $this->postJson("/api/v1/admin/game/series/{$sid}/titles", [
            'game_title_ids' => [$t3->id],
        ])->assertOk();

        $this->deleteJson("/api/v1/admin/game/series/{$sid}/titles/{$t1->id}")
            ->assertNoContent();

        $this->deleteJson("/api/v1/admin/game/series/{$sid}")->assertNoContent();
    }
}
