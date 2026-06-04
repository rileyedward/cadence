<?php

namespace App\Http\Controllers;

use App\Http\Requests\TemplateAssignment\StoreTemplateAssignmentRequest;
use App\Http\Requests\TemplateAssignment\UpdateTemplateAssignmentRequest;
use App\Models\Template;
use App\Models\TemplateAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TemplateAssignmentController extends Controller
{
    public function store(StoreTemplateAssignmentRequest $request, Template $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $template->assignments()->create($this->payload($request->validated()));

        return back();
    }

    public function update(UpdateTemplateAssignmentRequest $request, Template $template, TemplateAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $template);
        abort_unless($assignment->template_id === $template->id, 404);

        $assignment->update($this->payload($request->validated()));

        return back();
    }

    public function destroy(Request $request, Template $template, TemplateAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $template);
        abort_unless($assignment->template_id === $template->id, 404);

        $assignment->delete();

        return back();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'scope' => $data['scope'],
            'days_of_week' => $data['scope'] === 'custom' ? ($data['days_of_week'] ?? []) : null,
            'starts_on' => $data['starts_on'] ?? null,
            'ends_on' => $data['ends_on'] ?? null,
            'priority' => $data['priority'] ?? 0,
        ];
    }
}
