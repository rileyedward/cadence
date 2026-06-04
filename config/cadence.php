<?php

use App\Plugins\Samples\FitnessModule;
use App\Plugins\Samples\FocusSprintBlockType;

return [
    /*
    |--------------------------------------------------------------------------
    | Compiler tunables (doc 07)
    |--------------------------------------------------------------------------
    | All times are in minutes. The compiler/recompiler read these — no magic
    | numbers live inside the services.
    */
    'buffer_minutes' => env('CADENCE_BUFFER_MINUTES', 5),
    'min_rest_minutes' => env('CADENCE_MIN_REST_MINUTES', 15),
    'soft_tolerance_minutes' => env('CADENCE_SOFT_TOLERANCE_MINUTES', 30),

    // Retain the current schedule + this many recent versions per plan; prune the rest.
    'max_schedule_versions' => env('CADENCE_MAX_SCHEDULE_VERSIONS', 10),

    // Allowed skew (minutes) between a client-supplied fromMin and server "now" (doc 08).
    'runtime_from_skew_minutes' => env('CADENCE_RUNTIME_SKEW_MINUTES', 5),

    // Minute grid that drag/resize snaps to (doc 18).
    'snap_minutes' => env('CADENCE_SNAP_MINUTES', 5),

    /*
    | Energy adjacency rules — after a high+deep block, force min rest before the
    | next high/deep block (doc 07 step 6).
    */
    'energy_rules' => [
        'rest_after_high_deep' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Intelligence thresholds (doc 10)
    |--------------------------------------------------------------------------
    */
    'intelligence' => [
        'min_samples' => env('CADENCE_INTEL_MIN_SAMPLES', 3),
        'window_days' => env('CADENCE_INTEL_WINDOW_DAYS', 56), // ~8 week rolling window
        'frequency_cutoff' => env('CADENCE_INTEL_FREQUENCY_CUTOFF', 0.6), // 60% of occurrences
        'drift_threshold' => env('CADENCE_INTEL_DRIFT_THRESHOLD', 0.2), // 20% over/under estimate
    ],

    /*
    |--------------------------------------------------------------------------
    | Calendar integration (doc 15) — null driver keeps the app working with no
    | Google credentials configured.
    |--------------------------------------------------------------------------
    */
    'calendar' => [
        'driver' => env('CADENCE_CALENDAR_DRIVER', 'null'), // null | google
        'push_buffers' => env('CADENCE_CALENDAR_PUSH_BUFFERS', false),
        'marker' => 'cadence', // extended-property tag so re-push is idempotent
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT_URI'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins (doc 14) — explicit allow-list; no auto-scan of arbitrary code.
    |--------------------------------------------------------------------------
    */
    'plugins' => [
        'intent_modules' => array_filter([
            env('CADENCE_SAMPLE_PLUGINS', true) ? FitnessModule::class : null,
        ]),
        'block_types' => array_filter([
            env('CADENCE_SAMPLE_PLUGINS', true) ? FocusSprintBlockType::class : null,
        ]),
    ],
];
