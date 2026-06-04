<?php

namespace Database\Factories;

use App\Enums\TemplateScope;
use App\Models\Template;
use App\Models\TemplateAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TemplateAssignment>
 */
class TemplateAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'template_id' => Template::factory(),
            'scope' => TemplateScope::Weekday,
            'days_of_week' => null,
            'starts_on' => null,
            'ends_on' => null,
            'priority' => 0,
        ];
    }

    public function weekend(): static
    {
        return $this->state(['scope' => TemplateScope::Weekend]);
    }

    /** @param  array<int,int>  $days */
    public function custom(array $days): static
    {
        return $this->state(['scope' => TemplateScope::Custom, 'days_of_week' => $days]);
    }
}
