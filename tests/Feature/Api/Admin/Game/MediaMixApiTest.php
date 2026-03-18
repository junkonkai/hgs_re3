<?php

namespace Tests\Feature\Api\Admin\Game;

use App\Models\GameFranchise;
use App\Models\GameMediaMix;
use App\Models\GameRelatedProduct;
use App\Models\GameTitle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MediaMixApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeaders([
            'X-GPTS-API-KEY' => 'test-gpts-api-key',
        ]);
    }

    private function createFranchise(): GameFranchise
    {
        return GameFranchise::query()->create([
            'key' => 'f-mm-' . uniqid('', true),
            'name' => 'F',
            'phonetic' => 'えふ',
            'node_name' => 'F',
            'description' => null,
            'description_source' => null,
        ]);
    }

    private function createTitle(GameFranchise $f): GameTitle
    {
        return GameTitle::query()->create([
            'game_franchise_id' => $f->id,
            'name' => 'T',
            'key' => 'k-' . uniqid('', true),
            'phonetic' => 'てぃ',
            'node_name' => 'T',
            'first_release_int' => 20200101,
            'use_ogp_description' => false,
            'rating' => 0,
            'search_synonyms' => '',
            'description' => '',
            'description_source' => null,
            'issue' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mmPayload(GameFranchise $f, array $overrides = []): array
    {
        return array_merge([
            'type' => 1,
            'name' => 'ミックスA',
            'key' => 'mx-' . uniqid('', true),
            'node_name' => 'MA',
            'game_franchise_id' => $f->id,
            'game_media_mix_group_id' => null,
            'rating' => 0,
            'sort_order' => 1,
            'description' => '',
            'description_source' => null,
            'use_ogp_description' => false,
        ], $overrides);
    }

    public function test_crud_and_links(): void
    {
        $f = $this->createFranchise();
        $title = $this->createTitle($f);
        $rp = GameRelatedProduct::query()->create([
            'name' => 'RP',
            'node_name' => 'RP',
            'rating' => 0,
            'default_img_type' => 1,
            'description' => '',
            'description_source' => null,
            'sort_order' => 0,
        ]);

        $store = $this->postJson('/api/v1/admin/game/media-mixes', $this->mmPayload($f));
        $store->assertCreated();
        $mid = $store->json('data.id');

        $this->getJson('/api/v1/admin/game/media-mixes?q=' . urlencode('ミックス'))
            ->assertOk()
            ->assertJsonFragment(['id' => $mid]);

        $this->putJson("/api/v1/admin/game/media-mixes/{$mid}", $this->mmPayload($f, [
            'name' => 'ミックスB',
        ]))->assertOk()->assertJsonPath('data.name', 'ミックスB');

        $this->postJson("/api/v1/admin/game/media-mixes/{$mid}/titles", [
            'game_title_ids' => [$title->id],
        ])->assertOk();

        $this->postJson("/api/v1/admin/game/media-mixes/{$mid}/related-products", [
            'game_related_product_ids' => [$rp->id],
        ])->assertOk();

        $this->deleteJson("/api/v1/admin/game/media-mixes/{$mid}")->assertNoContent();
        $this->assertNull(GameMediaMix::query()->find($mid));
    }
}
