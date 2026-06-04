<?php

namespace App\Plugins;

use App\Plugins\Contracts\BlockType;
use App\Plugins\Contracts\IntentModule;

/**
 * Discovers plugins from the explicit config allow-list (no auto-scan of
 * arbitrary code, doc 14). Resolved lazily and memoized.
 */
class PluginRegistry
{
    /** @var array<int, IntentModule>|null */
    private ?array $intentModules = null;

    /** @var array<int, BlockType>|null */
    private ?array $blockTypes = null;

    /**
     * @return array<int, IntentModule>
     */
    public function intentModules(): array
    {
        return $this->intentModules ??= array_map(
            fn (string $class) => app($class),
            array_values(array_filter(
                (array) config('cadence.plugins.intent_modules', []),
                fn ($class) => is_string($class) && is_a($class, IntentModule::class, true),
            )),
        );
    }

    /**
     * @return array<int, BlockType>
     */
    public function blockTypes(): array
    {
        return $this->blockTypes ??= array_map(
            fn (string $class) => app($class),
            array_values(array_filter(
                (array) config('cadence.plugins.block_types', []),
                fn ($class) => is_string($class) && is_a($class, BlockType::class, true),
            )),
        );
    }

    /**
     * @return array<int, string>
     */
    public function activeModuleKeys(): array
    {
        return array_map(fn (IntentModule $m) => $m->key(), $this->intentModules());
    }

    /**
     * Serializable block-type definitions for the block editor.
     *
     * @return array<int, array{key:string, name:string, defaults:array<string,mixed>}>
     */
    public function blockTypeDefinitions(): array
    {
        return array_map(fn (BlockType $t) => [
            'key' => $t->key(),
            'name' => $t->name(),
            'defaults' => $t->defaults(),
        ], $this->blockTypes());
    }
}
