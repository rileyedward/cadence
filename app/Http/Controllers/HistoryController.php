<?php

namespace App\Http\Controllers;

use App\Queries\HistoryQuery;
use App\Services\Intelligence\Optimizer;
use App\Services\Intelligence\PatternAnalyzer;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $days = (int) config('cadence.intelligence.window_days', 56);

        $history = HistoryQuery::forRange(
            $user,
            CarbonImmutable::now($user->timezone)->subDays($days),
            CarbonImmutable::now($user->timezone),
        );

        return Inertia::render('History/Index', [
            'patterns' => (new PatternAnalyzer($history))->summary(),
            'recommendations' => (new Optimizer($history))->recommend(),
            'hasHistory' => $history->blocks()->isNotEmpty(),
        ]);
    }
}
