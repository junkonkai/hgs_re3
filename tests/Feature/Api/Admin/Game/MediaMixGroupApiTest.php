<?php

namespace Tests\Feature\Api\Admin\Game;

use App\Models\GameFranchise;
use App\Models\GameMediaMix;
use App\Models\GameMediaMixGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MediaMixGroupApiTest extends TestCase
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
            'key' => 'f-mmg-' . uniqid('', true),
            'name' => 'F',
            'phonetic' => 'えふ',
            'node_name' => 'F',
            'description' => null,
            'description_source' => null,
        ]);
    }

    private function createMediaMix(GameFranchise $f): GameMediaMix
    {
        return GameMediaMix::query()->create([
            'type' => 1,
            'name' => 'MM',
            'key' => 'k-' . uniqid('', true),
            'node_name' => 'MM',
            'game_franchise_id' => $f->id,
            'game_media_mix_group_id' => null,
            'rating' => 0,
            'sort_order' => 1,
            'description' => '',
            'description_source' => null,
            'use_ogp_description' => 0,
        ]);
    }

    public function test_crud_and_media_mix_assign(): void
    {
        $f = $this->createFranchise();
        $mm = $this->createMediaMix($f);

        $store = $this->postJson('/api/v1/admin/game/media-mix-groups', [
            'game_franchise_id' => $f->id,
            'name' => 'グループMM',
            'node_name' => 'GMM',
            'description' => null,
            'sort_order' => 1,
        ]);
        $store->assertCreated();
        $gid = $store->json('data.id');

        $this->postJson("/api/v1/admin/game/media-mix-groups/{$gid}/media-mixes", [
            'game_media_mix_ids' => [$mm->id],
        ])->assertOk();

        $mm->refresh();
        $this->assertSame($gid, $mm->game_media_mix_group_id);

        $this->getJson("/api/v1/admin/game/media-mix-groups/{$gid}/media-mixes")
            ->assertOk()
            ->assertJsonFragment(['id' => $mm->id]);

        $this->putJson("/api/v1/admin/game/media-mix-groups/{$gid}/media-mixes", [
            'game_media_mix_ids' => [],
        ])->assertOk();

        $mm->refresh();
        $this->assertNull($mm->game_media_mix_group_id);

        $this->deleteJson("/api/v1/admin/game/media-mix-groups/{$gid}")->assertNoContent();
        $this->assertNull(GameMediaMixGroup::query()->find($gid));
    }
}
