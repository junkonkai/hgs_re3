<?php

namespace Tests\Feature\Api\Admin\Game;

use App\Models\GameFranchise;
use App\Models\GameSeries;
use App\Models\GameTitle;

class FranchiseApiTest extends GameMasterApiTestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createFranchise(array $overrides = []): GameFranchise
    {
        return GameFranchise::query()->create(array_merge([
            'key' => 'f-'.uniqid('', true),
            'name' => 'テストフランチャイズ',
            'phonetic' => 'てすとふらんちゃいず',
            'node_name' => 'TF',
            'description' => null,
            'description_source' => null,
        ], $overrides));
    }

    public function test_index_returns_paginated_list(): void
    {
        $this->createFranchise();
        $this->createFranchise();

        $response = $this->getJson('/api/v1/admin/game/franchises?per_page=1');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'key',
                    'phonetic',
                    'node_name',
                    'description',
                    'description_source',
                ],
            ],
            'meta',
            'links',
        ]);
    }

    public function test_index_can_search_by_phonetic(): void
    {
        $f = $this->createFranchise([
            'name' => 'ユニークネームAAA',
            'phonetic' => 'ゆにーくよみがなびーびーびー',
        ]);

        $response = $this->getJson('/api/v1/admin/game/franchises?q='.urlencode('よみがなびーびー'));

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $f->id,
            'name' => 'ユニークネームAAA',
        ]);
    }

    public function test_show_returns_franchise(): void
    {
        $f = $this->createFranchise();

        $response = $this->getJson("/api/v1/admin/game/franchises/{$f->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $f->id);
        $response->assertJsonPath('data.key', $f->key);
    }

    public function test_store_and_update(): void
    {
        $key = 'nf-'.uniqid('', true);
        $store = $this->postJson('/api/v1/admin/game/franchises', [
            'name' => '新規F',
            'key' => $key,
            'phonetic' => 'しんきえふ',
            'node_name' => 'NF',
            'description' => '説明',
            'description_source' => null,
        ]);
        $store->assertCreated();
        $store->assertJsonPath('data.name', '新規F');

        $id = $store->json('data.id');
        $newKey = 'uf-'.uniqid('', true);
        $put = $this->putJson("/api/v1/admin/game/franchises/{$id}", [
            'name' => '更新F',
            'key' => $newKey,
            'phonetic' => 'こうしんえふ',
            'node_name' => 'UF',
            'description' => '',
            'description_source' => 'src',
        ]);
        $put->assertOk();
        $put->assertJsonPath('data.name', '更新F');
    }

    public function test_destroy_deletes_franchise(): void
    {
        $f = $this->createFranchise();

        $response = $this->deleteJson("/api/v1/admin/game/franchises/{$f->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('game_franchises', ['id' => $f->id]);
    }

    public function test_series_sync_and_detach(): void
    {
        $f = $this->createFranchise();
        $s1 = GameSeries::query()->create([
            'game_franchise_id' => null,
            'name' => 'シリーズ1',
            'phonetic' => 'しりーずいち',
            'node_name' => 'S1',
            'first_release_int' => 0,
            'description' => null,
            'description_source' => null,
        ]);
        $s2 = GameSeries::query()->create([
            'game_franchise_id' => null,
            'name' => 'シリーズ2',
            'phonetic' => 'しりーずに',
            'node_name' => 'S2',
            'first_release_int' => 0,
            'description' => null,
            'description_source' => null,
        ]);

        $this->postJson("/api/v1/admin/game/franchises/{$f->id}/series", [
            'game_series_ids' => [$s1->id],
        ])->assertOk();

        $this->postJson("/api/v1/admin/game/franchises/{$f->id}/series", [
            'game_series_ids' => [$s2->id],
        ])->assertOk();

        $index = $this->getJson("/api/v1/admin/game/franchises/{$f->id}/series");
        $index->assertOk();
        $index->assertJsonFragment(['id' => $s1->id, 'name' => 'シリーズ1']);

        $this->deleteJson("/api/v1/admin/game/franchises/{$f->id}/series/{$s1->id}")
            ->assertNoContent();

        $s1->refresh();
        $this->assertNull($s1->game_franchise_id);
    }

    public function test_titles_sync_detach_and_rejects_series_title(): void
    {
        $f = $this->createFranchise();
        $t1 = GameTitle::query()->create([
            'key' => 'ft1-'.uniqid('', true),
            'game_franchise_id' => null,
            'game_series_id' => null,
            'name' => '直下タ1',
            'phonetic' => 'ちょっかたいとるいち',
            'node_name' => 'T1',
            'first_release_int' => 0,
            'rating' => 0,
        ]);
        $t2 = GameTitle::query()->create([
            'key' => 'ft2-'.uniqid('', true),
            'game_franchise_id' => null,
            'game_series_id' => null,
            'name' => '直下タ2',
            'phonetic' => 'ちょっかたいとるに',
            'node_name' => 'T2',
            'first_release_int' => 0,
            'rating' => 0,
        ]);

        $this->postJson("/api/v1/admin/game/franchises/{$f->id}/titles", [
            'game_title_ids' => [$t1->id],
        ])->assertOk();

        $this->postJson("/api/v1/admin/game/franchises/{$f->id}/titles", [
            'game_title_ids' => [$t2->id],
        ])->assertOk();

        $this->getJson("/api/v1/admin/game/franchises/{$f->id}/titles")
            ->assertOk()
            ->assertJsonFragment(['id' => $t1->id, 'name' => '直下タ1']);

        $this->putJson("/api/v1/admin/game/franchises/{$f->id}/titles", [
            'game_title_ids' => [$t1->id],
        ])->assertOk();

        $this->getJson("/api/v1/admin/game/franchises/{$f->id}/titles")
            ->assertOk()
            ->assertJsonMissing(['id' => $t2->id, 'name' => '直下タ2']);

        $this->deleteJson("/api/v1/admin/game/franchises/{$f->id}/titles/{$t1->id}")
            ->assertNoContent();

        $series = GameSeries::query()->create([
            'game_franchise_id' => $f->id,
            'name' => '所属シリーズ',
            'phonetic' => 'しょぞくしりーず',
            'node_name' => 'SS',
            'first_release_int' => 0,
            'description' => null,
            'description_source' => null,
        ]);
        // GameTitle::save は franchise_id と series_id 両方あると series_id を落とすため、シリーズ配下は franchise_id null
        $tSeries = GameTitle::query()->create([
            'key' => 'fts-'.uniqid('', true),
            'game_franchise_id' => null,
            'game_series_id' => $series->id,
            'name' => 'シリーズ配下',
            'phonetic' => 'しりーずはいか',
            'node_name' => 'TS',
            'first_release_int' => 0,
            'rating' => 0,
        ]);

        $this->assertNotNull(
            GameTitle::query()->whereKey($tSeries->id)->value('game_series_id')
        );

        $this->putJson("/api/v1/admin/game/franchises/{$f->id}/titles", [
            'game_title_ids' => [$tSeries->id],
        ])->assertStatus(422);

        $this->postJson("/api/v1/admin/game/franchises/{$f->id}/titles", [
            'game_title_ids' => [$tSeries->id],
        ])->assertStatus(422);
    }
}
