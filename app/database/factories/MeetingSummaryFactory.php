<?php

namespace Database\Factories;

use App\Models\MeetingSummary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\MeetingSummary>
 */
class MeetingSummaryFactory extends Factory
{
    protected $model = MeetingSummary::class;

    public function definition(): array
    {
        $org = \App\Models\Organization::factory()->create();
        return [
            'organization_id' => $org->id,
            'title' => $this->faker->sentence(4),
            'summary' => null,
            'decisions' => null,
            'action_items' => null,
            'source' => null,
            'azure_raw' => null,
            'input_type' => 'text',
            'input_text' => $this->faker->paragraph(),
            'input_media_path' => null,
            'processing_status' => 'pending',
            'error_message' => null,
        ];
    }
}
