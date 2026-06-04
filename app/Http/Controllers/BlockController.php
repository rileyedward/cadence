<?php

namespace App\Http\Controllers;

use App\Http\Requests\Block\ReorderBlocksRequest;
use App\Http\Requests\Block\StoreBlockRequest;
use App\Http\Requests\Block\UpdateBlockRequest;
use App\Models\Block;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function store(StoreBlockRequest $request, Template $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $data = $request->validated();

        $block = $template->blocks()->create([
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'flexibility_mode' => $data['flexibility_mode'],
            'category' => $data['category'] ?? null,
            'priority' => $data['priority'] ?? 0,
            'constraints' => $data['constraints'] ?? null,
            'context' => $data['context'] ?? null,
            'order' => $template->blocks()->max('order') + 1,
        ]);

        $this->syncDefaultIntents($block, $data['default_intents'] ?? []);

        return back();
    }

    public function update(UpdateBlockRequest $request, Block $block): RedirectResponse
    {
        $this->authorize('update', $block);

        $data = $request->validated();

        $block->update([
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'flexibility_mode' => $data['flexibility_mode'],
            'category' => $data['category'] ?? null,
            'priority' => $data['priority'] ?? 0,
            'constraints' => $data['constraints'] ?? null,
            'context' => $data['context'] ?? null,
        ]);

        $this->syncDefaultIntents($block, $data['default_intents'] ?? []);

        return back();
    }

    public function destroy(Request $request, Block $block): RedirectResponse
    {
        $this->authorize('delete', $block);

        $block->delete();

        return back();
    }

    public function reorder(ReorderBlocksRequest $request, Template $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $ids = $request->validated('block_ids');

        // Only reorder blocks that actually belong to this template.
        $owned = $template->blocks()->whereIn('id', $ids)->pluck('id')->all();

        foreach ($ids as $order => $id) {
            if (in_array($id, $owned, true)) {
                Block::where('id', $id)->update(['order' => $order]);
            }
        }

        return back();
    }

    /**
     * @param  array<int, array{intent_id: int, weight?: int}>  $defaults
     */
    private function syncDefaultIntents(Block $block, array $defaults): void
    {
        $payload = [];
        foreach ($defaults as $entry) {
            $payload[$entry['intent_id']] = ['weight' => $entry['weight'] ?? 1];
        }

        $block->defaultIntents()->sync($payload);
    }
}
