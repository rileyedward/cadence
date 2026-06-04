<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // System libraries are always seeded.
        $this->call([
            IntentLibrarySeeder::class,
            ActivityLibrarySeeder::class,
        ]);

        // Register any enabled plugin modules into the library (idempotent, doc 14).
        Artisan::call('cadence:sync-plugins');

        // Dev/demo data only outside production.
        if (! app()->environment('production')) {
            User::factory()->create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
            ]);

            $this->call(SampleTemplateSeeder::class);
        }
    }
}
