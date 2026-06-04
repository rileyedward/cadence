<?php

namespace App\Services\Compiler;

use App\Enums\PlanStatus;
use App\Models\DailyPlan;
use App\Models\Schedule;
use App\Support\LogicalDay;

/**
 * Facade: builds inputs from a plan, runs the compiler, persists the schedule,
 * and advances the plan status. Used by ScheduleController@generate.
 */
class ScheduleGenerator
{
    public function __construct(
        private readonly CompilerInputBuilder $builder,
        private readonly TimelineCompiler $compiler,
        private readonly ScheduleWriter $writer,
    ) {}

    public function generate(DailyPlan $plan): Schedule
    {
        $inputs = $this->builder->forPlan($plan);
        $result = $this->compiler->compile($inputs, CompileConfig::fromConfig());
        $schedule = $this->writer->write($plan, $result);

        // Draft -> generated; today's plan becomes active (lifecycle also enforces this).
        if ($plan->status === PlanStatus::Draft || $plan->status === PlanStatus::Generated) {
            $isToday = LogicalDay::currentDate($plan->user)->toDateString() === $plan->date->toDateString();
            $plan->update(['status' => $isToday ? PlanStatus::Active : PlanStatus::Generated]);
        }

        return $schedule;
    }

    /**
     * Compile without persisting — for the live planning preview (doc 07).
     */
    public function preview(DailyPlan $plan, array $overrides = []): CompileResult
    {
        return $this->compiler->compile($this->builder->forPlan($plan, $overrides), CompileConfig::fromConfig());
    }
}
