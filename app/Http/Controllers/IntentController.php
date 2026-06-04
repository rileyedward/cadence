<?php

namespace App\Http\Controllers;

use App\Http\Requests\Intent\StoreIntentRequest;
use App\Http\Requests\Intent\UpdateIntentRequest;
use App\Http\Resources\IntentResource;
use App\Models\Intent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class IntentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $intents = Intent::query()
            ->libraryFor($user)
            ->with('activities')
            ->orderBy('name')
            ->get();

        return Inertia::render('Intents/Index', [
            'intents' => IntentResource::collection($intents),
        ]);
    }

    public function store(StoreIntentRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        Intent::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($user->id, $data['name']),
            'color' => $data['color'] ?? null,
            'icon' => $data['icon'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        return back();
    }

    public function update(UpdateIntentRequest $request, Intent $intent): RedirectResponse
    {
        $this->authorize('update', $intent);

        $intent->update($request->safe()->only(['name', 'color', 'icon', 'description']));

        return back();
    }

    public function destroy(Request $request, Intent $intent): RedirectResponse
    {
        $this->authorize('delete', $intent);

        $intent->delete();

        return back();
    }

    /**
     * Clone-on-edit: copy a viewable (typically system) intent into a user-owned custom one.
     */
    public function clone(Request $request, Intent $intent): RedirectResponse
    {
        $this->authorize('view', $intent);

        $user = $request->user();

        $copy = Intent::create([
            'user_id' => $user->id,
            'name' => $intent->name,
            'slug' => $this->uniqueSlug($user->id, $intent->name),
            'color' => $intent->color,
            'icon' => $intent->icon,
            'description' => $intent->description,
        ]);

        // Carry over the source intent's suggested activities.
        $copy->activities()->sync(
            $intent->activities->pluck('pivot.weight', 'id')->map(fn ($w) => ['weight' => $w])->all()
        );

        return back();
    }

    private function uniqueSlug(int $userId, string $name): string
    {
        $base = Str::slug($name) ?: 'intent';
        $slug = $base;
        $i = 2;

        while (Intent::where('user_id', $userId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
