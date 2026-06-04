<?php

namespace App\Http\Controllers;

use App\Http\Requests\Template\StoreTemplateRequest;
use App\Http\Requests\Template\UpdateTemplateRequest;
use App\Http\Resources\TemplateResource;
use App\Models\BlockDependency;
use App\Models\Template;
use App\Plugins\PluginRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $templates = Template::query()
            ->where('user_id', $request->user()->id)
            ->withCount('blocks')
            ->with('assignments')
            ->orderBy('name')
            ->get();

        return Inertia::render('Templates/Index', [
            'templates' => TemplateResource::collection($templates),
        ]);
    }

    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $template = Template::create([
            'user_id' => $request->user()->id,
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return to_route('templates.edit', $template);
    }

    public function edit(Request $request, Template $template, PluginRegistry $plugins): Response
    {
        $this->authorize('view', $template);

        $template->load(['blocks.defaultIntents', 'assignments']);

        return Inertia::render('Templates/Edit', [
            'template' => new TemplateResource($template),
            'blockTypes' => $plugins->blockTypeDefinitions(),
        ]);
    }

    public function update(UpdateTemplateRequest $request, Template $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $template->update([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back();
    }

    public function destroy(Request $request, Template $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return to_route('templates.index');
    }

    /**
     * Deep-copy a template: blocks, their default intents, and dependencies.
     */
    public function fork(Request $request, Template $template): RedirectResponse
    {
        $this->authorize('fork', $template);

        $copy = DB::transaction(function () use ($request, $template) {
            $template->load(['blocks.defaultIntents', 'blocks.dependencies', 'assignments']);

            $copy = Template::create([
                'user_id' => $request->user()->id,
                'name' => $template->name.' (copy)',
                'description' => $template->description,
                'is_active' => $template->is_active,
                'forked_from_id' => $template->id,
            ]);

            // old block id => new block id, to remap dependencies afterwards.
            $idMap = [];

            foreach ($template->blocks as $block) {
                $newBlock = $copy->blocks()->create([
                    'name' => $block->name,
                    'start_time' => $block->start_time,
                    'end_time' => $block->end_time,
                    'flexibility_mode' => $block->flexibility_mode,
                    'category' => $block->category,
                    'priority' => $block->priority,
                    'constraints' => $block->constraints,
                    'context' => $block->context,
                    'order' => $block->order,
                ]);
                $idMap[$block->id] = $newBlock->id;

                $newBlock->defaultIntents()->sync(
                    $block->defaultIntents->pluck('pivot.weight', 'id')
                        ->map(fn ($w) => ['weight' => $w])->all()
                );
            }

            // Remap block dependencies onto the copied blocks.
            foreach ($template->blocks as $block) {
                foreach ($block->dependencies as $dep) {
                    if (isset($idMap[$dep->block_id], $idMap[$dep->depends_on_block_id])) {
                        BlockDependency::create([
                            'block_id' => $idMap[$dep->block_id],
                            'depends_on_block_id' => $idMap[$dep->depends_on_block_id],
                            'type' => $dep->type,
                        ]);
                    }
                }
            }

            // Copy assignments too so the fork is immediately usable.
            foreach ($template->assignments as $a) {
                $copy->assignments()->create([
                    'scope' => $a->scope,
                    'days_of_week' => $a->days_of_week,
                    'starts_on' => $a->starts_on,
                    'ends_on' => $a->ends_on,
                    'priority' => $a->priority,
                ]);
            }

            return $copy;
        });

        return to_route('templates.edit', $copy);
    }
}
