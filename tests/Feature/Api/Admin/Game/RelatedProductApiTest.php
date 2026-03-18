<?php

namespace Tests\Feature\Api\Admin\Game;

use App\Enums\Shop;
use App\Models\GameFranchise;
use App\Models\GamePlatform;
use App\Models\GameRelatedProduct;
use App\Models\GameTitle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RelatedProductApiTest extends TestCase
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
            'key' => 'f-rp-' . uniqid('', true),
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

    private function createPlatform(): GamePlatform
    {
        return GamePlatform::query()->create([
            'key' => 'p-rp-' . uniqid('', true),
            'game_maker_id' => null,
            'name' => 'Plat',
            'acronym' => 'P',
            'node_name' => 'P',
            'type' => 1,
            'sort_order' => 0,
            'description' => '',
            'description_source' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rpPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => '関連商品A',
            'node_name' => 'RA',
            'rating' => 0,
            'default_img_type' => 1,
            'description' => '',
            'description_source' => null,
            'sort_order' => 0,
        ], $overrides);
    }

    public function test_crud_links_and_shop(): void
    {
        $f = $this->createFranchise();
        $title = $this->createTitle($f);
        $plat = $this->createPlatform();
        $mm = \App\Models\GameMediaMix::query()->create([
            'type' => 1,
            'name' => 'MM',
            'key' => 'mm-' . uniqid('', true),
            'node_name' => 'MM',
            'game_franchise_id' => $f->id,
            'game_media_mix_group_id' => null,
            'rating' => 0,
            'sort_order' => 1,
            'description' => '',
            'description_source' => null,
            'use_ogp_description' => 0,
        ]);

        $store = $this->postJson('/api/v1/admin/game/related-products', $this->rpPayload());
        $store->assertCreated();
        $rid = $store->json('data.id');

        $this->getJson('/api/v1/admin/game/related-products?q=' . urlencode('関連'))
            ->assertOk()
            ->assertJsonFragment(['id' => $rid]);

        $this->postJson("/api/v1/admin/game/related-products/{$rid}/platforms", [
            'game_platform_ids' => [$plat->id],
        ])->assertOk();

        $this->postJson("/api/v1/admin/game/related-products/{$rid}/titles", [
            'game_title_ids' => [$title->id],
        ])->assertOk();

        $this->postJson("/api/v1/admin/game/related-products/{$rid}/media-mixes", [
            'game_media_mix_ids' => [$mm->id],
        ])->assertOk();

        $this->getJson("/api/v1/admin/game/related-products/{$rid}/platforms")
            ->assertJsonFragment(['id' => $plat->id]);

        $shop = $this->postJson("/api/v1/admin/game/related-products/{$rid}/shops", [
            'shop_id' => Shop::Steam->value,
            'url' => 'https://store.example.com/x',
        ]);
        $shop->assertCreated();
        $sid = $shop->json('data.id');

        $this->deleteJson("/api/v1/admin/game/related-products/{$rid}/shops/{$sid}")->assertNoContent();

        $this->deleteJson("/api/v1/admin/game/related-products/{$rid}")->assertNoContent();
        $this->assertNull(GameRelatedProduct::query()->find($rid));
    }
}
