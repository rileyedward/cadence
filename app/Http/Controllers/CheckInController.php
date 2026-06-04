<?php

namespace App\Http\Controllers;

use App\Enums\CheckInStatus;
use App\Http\Requests\CheckIn\StoreCheckInRequest;
use App\Models\CheckIn;
use App\Models\DailyPlanBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    /**
     * Log a check-in on a plan block. `start` defaults actual_start to now;
     * `complete` defaults actual_end to now (doc 09).
     */
    public function store(StoreCheckInRequest $request, DailyPlanBlock $block): RedirectResponse
    {
        $this->authorize('update', $block);

        $status = CheckInStatus::from($request->validated('status'));

        $checkIn = $block->checkIns()->create([
            'status' => $status,
            'actual_start' => $request->date('actual_start')
                ?? ($status === CheckInStatus::Started ? now() : null),
            'actual_end' => $request->date('actual_end')
                ?? ($status === CheckInStatus::Completed ? now() : null),
            'note' => $request->validated('note'),
        ]);

        return back();
    }

    public function destroy(Request $request, CheckIn $checkIn): RedirectResponse
    {
        $this->authorize('update', $checkIn->dailyPlanBlock);

        $checkIn->delete();

        return back();
    }
}
