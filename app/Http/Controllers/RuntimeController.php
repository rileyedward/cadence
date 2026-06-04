<?php

namespace App\Http\Controllers;

use App\Http\Requests\Runtime\RuntimeOpRequest;
use App\Models\DailyPlan;
use App\Services\Compiler\Recompiler;
use App\Support\LogicalDay;
use Illuminate\Http\RedirectResponse;

class RuntimeController extends Controller
{
    private const OPS = ['extend', 'shorten', 'swap', 'skip', 'merge', 'split', 'drag', 'recompile'];

    public function __construct(private readonly Recompiler $recompiler) {}

    public function handle(RuntimeOpRequest $request, DailyPlan $plan, string $op): RedirectResponse
    {
        $this->authorize('update', $plan);
        abort_unless(in_array($op, self::OPS, true), 404);

        $schedule = $plan->currentSchedule()->with('events')->first();
        abort_if($schedule === null, 409, 'Generate a schedule before adjusting the day.');

        $fromMin = $this->resolveFromMin($plan, $request->integer('from_min'));

        $ops = [array_merge(['op' => $op], $request->safe()->except('from_min'))];

        $this->recompiler->recompile($schedule, $fromMin, $ops);

        return back();
    }

    /**
     * Server is authoritative for "now": clamp any client-supplied fromMin to a
     * small skew window around the server's current minute (doc 08).
     */
    private function resolveFromMin(DailyPlan $plan, ?int $clientFromMin): int
    {
        $server = LogicalDay::currentMinute($plan->user);

        if ($clientFromMin === null) {
            return $server;
        }

        $skew = (int) config('cadence.runtime_from_skew_minutes', 5);

        return max($server - $skew, min($clientFromMin, $server + $skew));
    }
}
