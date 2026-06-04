<?php

namespace App\Http\Controllers;

use App\Http\Requests\Activity\StoreActivityRequest;
use App\Http\Requests\Activity\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $activities = Activity::query()
            ->libraryFor($user)
            ->with('intents')
            ->orderBy('name')
            ->get();

        return Inertia::render('Activities/Index', [
            'activities' => ActivityResource::collection($activities),
        ]);
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $activity = Activity::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'default_duration_minutes' => $data['default_duration_minutes'],
            'tags' => $data['tags'] ?? [],
        ]);

        $activity->intents()->sync($this->intentSyncPayload($data['intent_ids'] ?? []));

        return back();
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $data = $request->validated();

        $activity->update([
            'name' => $data['name'],
            'default_duration_minutes' => $data['default_duration_minutes'],
            'tags' => $data['tags'] ?? [],
        ]);

        $activity->intents()->sync($this->intentSyncPayload($data['intent_ids'] ?? []));

        return back();
    }

    public function destroy(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);

        $activity->delete();

        return back();
    }

    /**
     * @param  array<int, int>  $intentIds
     * @return array<int, array{weight: int}>
     */
    private function intentSyncPayload(array $intentIds): array
    {
        $payload = [];
        foreach ($intentIds as $id) {
            $payload[$id] = ['weight' => 1];
        }

        return $payload;
    }
}
