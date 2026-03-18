<?php

namespace Tests\Feature\Api\Admin\Game;

use App\Models\GameFranchise;
use App\Models\GamePackage;
use App\Models\GamePackageGroup;
use App\Models\GamePlatform;
use App\Models\GameTitle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PackageGroupApiTest extends TestCase
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
            'key' => 'f-pg-' . uniqid('', true),
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
            'key' => 'p-pg-' . uniqid('', true),
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

    public function test_crud_and_title_package_links(): void
    {
        $f = $this->createFranchise();
        $title = $this->createTitle($f);
        $plat = $this->createPlatform();
        $pkg = GamePackage::query()->create([
            'name' => 'PKG',
            'acronym' => null,
            'node_name' => 'N',
            'game_platform_id' => $plat->id,
            'release_at' => '2020-01-01',
            'sort_order' => 0,
            'default_img_type' => 1,
            'rating' => 0,
        ]);

        $store = $this->postJson('/api/v1/admin/game/package-groups', [
            'name' => 'グループA',
            'node_name' => 'GA',
            'sort_order' => 1,
            'description' => null,
            'description_source' => null,
            'simple_shop_text' => null,
        ]);
        $store->assertCreated();
        $gid = $store->json('data.id');

        $this->getJson('/api/v1/admin/game/package-groups?q=' . urlencode('グループ'))
            ->assertOk()
            ->assertJsonFragment(['id' => $gid]);

        $this->putJson("/api/v1/admin/game/package-groups/{$gid}", [
            'name' => 'グループB',
            'node_name' => 'GB',
            'sort_order' => 2,
            'description' => null,
            'description_source' => null,
            'simple_shop_text' => null,
        ])->assertOk()->assertJsonPath('data.name', 'グループB');

        $this->postJson("/api/v1/admin/game/package-groups/{$gid}/titles", [
            'game_title_ids' => [$title->id],
        ])->assertOk();

        $this->getJson("/api/v1/admin/game/package-groups/{$gid}/titles")
            ->assertOk()
            ->assertJsonFragment(['id' => $title->id]);

        $this->putJson("/api/v1/admin/game/package-groups/{$gid}/titles", [
            'game_title_ids' => [],
        ])->assertOk();

        $this->postJson("/api/v1/admin/game/package-groups/{$gid}/packages", [
            'game_package_ids' => [$pkg->id],
        ])->assertOk();

        $this->getJson("/api/v1/admin/game/package-groups/{$gid}/packages")
            ->assertOk()
            ->assertJsonFragment(['id' => $pkg->id]);

        $this->deleteJson("/api/v1/admin/game/package-groups/{$gid}/packages/{$pkg->id}")
            ->assertNoContent();

        $this->deleteJson("/api/v1/admin/game/package-groups/999999")->assertNotFound();
    }
}
