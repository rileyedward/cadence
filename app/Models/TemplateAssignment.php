<?php

namespace App\Models;

use App\Enums\TemplateScope;
use Database\Factories\TemplateAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateAssignment extends Model
{
    /** @use HasFactory<TemplateAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'template_id',
        'scope',
        'days_of_week',
        'starts_on',
        'ends_on',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'scope' => TemplateScope::class,
            'days_of_week' => 'array',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'priority' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
