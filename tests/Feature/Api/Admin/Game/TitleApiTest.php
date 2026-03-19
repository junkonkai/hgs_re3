<?php

namespace Tests\Feature\Api\Admin\Game;

use App\Models\GameFranchise;
use App\Models\GameMediaMix;
use App\Models\GamePackageGroup;
use App\Models\GameRelatedProduct;

class TitleApiTest extends GameMasterApiTestCase
{
    private function createFranchise(): GameFranchise
    {
        return GameFranchise::query()->create([
            'key' => 'tf-'.uniqid('', true),
            'name' => 'FF',
            'phonetic' => 'えふえふ',
            'node_name' => 'FF',
            'description' => null,
            'description_source' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseTitlePayload(GameFranchise $f): array
    {
        return [
            'game_franchise_id' => $f->id,
            'name' => 'APIタイトル',
            'key' => 'tk-'.uniqid('', true),
            'phonetic' => 'えーぴーあいたいとる',
            'node_name' => 'AT',
            'first_release_int' => 20200101,
            'use_ogp_description' => false,
            'rating' => 0,
            'search_synonyms' => "検索用別名\r\n",
            'description' => '',
            'description_source' => null,
            'issue' => null,
        ];
    }

    public function test_index_show_store_update_destroy(): void
    {
        $f = $this->createFranchise();
        $payload = $this->baseTitlePayload($f);

        $store = $this->postJson('/api/v1/admin/game/titles', $payload);
        $store->assertCreated();
        $id = $store->json('data.id');

        $this->getJson('/api/v1/admin/game/titles?per_page=5')->assertOk();
        $this->getJson('/api/v1/admin/game/titles?q='.urlencode('検索用別名'))->assertOk()
            ->assertJsonFragment(['id' => $id]);

        $this->getJson("/api/v1/admin/game/titles/{$id}")->assertOk();

        $payload['name'] = '更新タイトル';
        $payload['phonetic'] = 'こうしんたいとる';
        $this->putJson("/api/v1/admin/game/titles/{$id}", $payload)->assertOk()
            ->assertJsonPath('data.name', '更新タイトル');

        $this->deleteJson("/api/v1/admin/game/titles/{$id}")->assertNoContent();
    }

    public function test_package_groups_related_products_media_mixes(): void
    {
        $f = $this->createFranchise();
        $store = $this->postJson('/api/v1/admin/game/titles', $this->baseTitlePayload($f));
        $store->assertCreated();
        $tid = $store->json('data.id');

        $g1 = GamePackageGroup::query()->create([
            'name' => 'PG1',
            'node_name' => 'PG1',
            'sort_order' => 0,
            'description' => null,
            'description_source' => null,
            'simple_shop_text' => null,
        ]);
        $g2 = GamePackageGroup::query()->create([
            'name' => 'PG2',
            'node_name' => 'PG2',
            'sort_order' => 0,
            'description' => null,
            'description_source' => null,
            'simple_shop_text' => null,
        ]);

        $this->postJson("/api/v1/admin/game/titles/{$tid}/package-groups", [
            'game_package_group_ids' => [$g1->id, $g2->id],
        ])->assertOk();

        $this->getJson("/api/v1/admin/game/titles/{$tid}/package-groups")->assertOk()
            ->assertJsonFragment(['id' => $g1->id]);

        $this->putJson("/api/v1/admin/game/titles/{$tid}/package-groups", [
            'game_package_group_ids' => [$g1->id],
        ])->assertOk();

        $this->deleteJson("/api/v1/admin/game/titles/{$tid}/package-groups/{$g1->id}")->assertNoContent();

        $rp = GameRelatedProduct::query()->create([
            'name' => 'RP',
            'node_name' => 'RP',
            'description' => '',
        ]);

        $this->postJson("/api/v1/admin/game/titles/{$tid}/related-products", [
            'game_related_product_ids' => [$rp->id],
        ])->assertOk();

        $this->putJson("/api/v1/admin/game/titles/{$tid}/related-products", [
            'game_related_product_ids' => [],
        ])->assertOk();

        $this->postJson("/api/v1/admin/game/titles/{$tid}/related-products", [
            'game_related_product_ids' => [$rp->id],
        ])->assertOk();

        $this->deleteJson("/api/v1/admin/game/titles/{$tid}/related-products/{$rp->id}")->assertNoContent();

        $mm = GameMediaMix::query()->create([
            'key' => 'mm-'.uniqid('', true),
            'type' => 1,
            'name' => 'MM',
            'node_name' => 'MM',
            'game_franchise_id' => null,
            'game_media_mix_group_id' => null,
            'rating' => 0,
            'sort_order' => 1,
            'description' => '',
            'description_source' => null,
            'use_ogp_description' => 0,
        ]);

        $this->postJson("/api/v1/admin/game/titles/{$tid}/media-mixes", [
            'game_media_mix_ids' => [$mm->id],
        ])->assertOk();

        $this->putJson("/api/v1/admin/game/titles/{$tid}/media-mixes", [
            'game_media_mix_ids' => [],
        ])->assertOk();

        $this->postJson("/api/v1/admin/game/titles/{$tid}/media-mixes", [
            'game_media_mix_ids' => [$mm->id],
        ])->assertOk();

        $this->deleteJson("/api/v1/admin/game/titles/{$tid}/media-mixes/{$mm->id}")->assertNoContent();

        $this->deleteJson("/api/v1/admin/game/titles/{$tid}")->assertNoContent();
    }
}
