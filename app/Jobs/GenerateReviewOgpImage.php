<?php

namespace App\Jobs;

use App\Models\UserGameTitleFearMeter;
use App\Models\UserGameTitleReview;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class GenerateReviewOgpImage implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $reviewId) {}

    public function handle(): void
    {
        $review = UserGameTitleReview::with(['user', 'gameTitle'])
            ->find($this->reviewId);

        if (!$review) {
            Log::warning("GenerateReviewOgpImage: review not found", ['review_id' => $this->reviewId]);
            return;
        }

        $fearMeterRecord = UserGameTitleFearMeter::where('user_id', $review->user_id)
            ->where('game_title_id', $review->game_title_id)
            ->first();

        $payload = [
            'review_id'        => $review->id,
            'game_title_name'  => $review->gameTitle->name,
            'show_id'          => $review->user->show_id,
            'total_score'      => $review->total_score,
            'fear_meter'       => $fearMeterRecord?->fear_meter?->value,
            'score_story'      => $review->score_story,
            'score_atmosphere' => $review->score_atmosphere,
            'score_gameplay'   => $review->score_gameplay,
            'has_spoiler'      => (bool) $review->has_spoiler,
        ];

        $result = Process::run(
            [config('services.ogp.binary'), json_encode($payload)],
            env: [
                'OUTPUT_DIR'        => config('services.ogp.output_dir'),
                'FONT_PATH'         => config('services.ogp.font_path'),
                'SVG_TEMPLATE_PATH' => config('services.ogp.template_path'),
            ]
        );

        if (!$result->successful()) {
            Log::error("GenerateReviewOgpImage: process failed", [
                'review_id' => $this->reviewId,
                'exit_code' => $result->exitCode(),
                'output'    => $result->output(),
                'error'     => $result->errorOutput(),
            ]);
            return;
        }

        $json = json_decode($result->output(), true);

        if (!($json['ok'] ?? false)) {
            Log::error("GenerateReviewOgpImage: generator returned error", [
                'review_id' => $this->reviewId,
                'error'     => $json['error'] ?? 'unknown',
            ]);
            return;
        }

        $review->update([
            'ogp_image_path' => 'public/img/review/' . $json['filename'],
        ]);
    }
}
