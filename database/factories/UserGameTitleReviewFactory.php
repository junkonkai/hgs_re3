<?php

namespace Database\Factories;

use App\Enums\PlayStatus;
use App\Models\UserGameTitleReview;
use App\Models\UserGameTitleReviewLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserGameTitleReview>
 */
class UserGameTitleReviewFactory extends Factory
{
    protected $model = UserGameTitleReview::class;

    public function definition(): array
    {
        $validScores     = [0, 5, 10, 15, 20];
        $scoreStory      = fake()->boolean(70) ? fake()->randomElement($validScores) : null;
        $scoreAtmosphere = fake()->boolean(70) ? fake()->randomElement($validScores) : null;
        $scoreGameplay   = fake()->boolean(70) ? fake()->randomElement($validScores) : null;
        $fearMeter       = fake()->boolean(70) ? fake()->numberBetween(0, 4) : null;
        $adjustment      = fake()->boolean(50) ? fake()->numberBetween(-20, 20) : null;
        $baseScore       = UserGameTitleReview::calcBaseScore($fearMeter, $scoreStory, $scoreAtmosphere, $scoreGameplay);
        $totalScore      = UserGameTitleReview::calcTotalScore($baseScore, $adjustment);

        return [
            'play_status'           => fake()->randomElement(PlayStatus::cases())->value,
            'body'                  => fake()->paragraphs(fake()->numberBetween(2, 5), true),
            'has_spoiler'           => fake()->boolean(20),
            'score_story'           => $scoreStory,
            'score_atmosphere'      => $scoreAtmosphere,
            'score_gameplay'        => $scoreGameplay,
            'user_score_adjustment' => $adjustment,
            'base_score'            => $baseScore,
            'total_score'           => $totalScore,
            'current_log_id'        => null,
            'is_hidden'             => false,
            'is_deleted'            => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (UserGameTitleReview $review) {
            $log = UserGameTitleReviewLog::create([
                'review_id'             => $review->id,
                'user_id'               => $review->user_id,
                'version'               => 1,
                'play_status'           => $review->play_status->value,
                'body'                  => $review->body,
                'has_spoiler'           => $review->has_spoiler,
                'score_story'           => $review->score_story,
                'score_atmosphere'      => $review->score_atmosphere,
                'score_gameplay'        => $review->score_gameplay,
                'user_score_adjustment' => $review->user_score_adjustment,
                'base_score'            => $review->base_score,
                'total_score'           => $review->total_score,
            ]);

            $review->update(['current_log_id' => $log->id]);
        });
    }
}
