# Cadence

Compile your intentions into a day.

## Overview

### What is Cadence?

Cadence is a **life-structure compiler**: it turns flexible human intention into an adaptive daily timeline. You don't schedule individual tasks. Instead you define recurring **routine templates** made of time **blocks**, choose each day what *state* you'll be in for each block (an **intent** plus optional activities), and let Cadence compile those decisions into a concrete, ordered timeline — complete with buffers and transitions — that reflows live as your day changes.

The guiding question is *"What state am I in during this block today?"* rather than *"What task must I do at this time?"*

Cadence ships as a mobile-first, installable PWA.

### Why Use Cadence?

Rigid calendars and task lists assume your day goes exactly as planned. It never does. Cadence is built for the way life actually moves: you set the structure once, make lightweight per-block decisions each morning, and adjust on the fly while the system reflows everything downstream for you. It is **not** a task manager, a rigid calendar, or a habit tracker — it's the layer that translates how you *want* to spend your energy into a timeline you can actually follow.

### Key Features

- **Routine Templates**: Build reusable weekly structures from ordered time blocks, then assign them to weekdays, weekends, or custom date ranges.
- **Daily Intents**: Each day, instantiate a template and pick the behavioral state for each block — recovery, deep work, movement, and more — with optional concrete activities, energy, and focus settings.
- **Deterministic Compiler**: A PHP-only engine compiles your decisions into a versioned, ordered timeline with buffers and transitions. The server is always the single source of truth; the client shows a cosmetic preview.
- **Live Runtime Adjustments**: Edit your day as it happens and watch the rest of the timeline reflow within each block's flexibility tolerance.
- **Check-ins & History**: Log started, completed, or skipped against each block with actual start and end times.
- **Google Calendar Sync**: Optional two-way calendar integration, with a no-op driver so everything keeps working without it.

## Getting Started

### Prerequisites

Ensure you have the following installed. Verify each by running the provided command in your terminal.

1. **PHP 8.3+** is required for the application. Check with:

    ```bash
    php --version
    ```

2. **Composer** manages PHP dependencies. Verify with:

    ```bash
    composer --version
    ```

3. **Node** and **NPM** manage frontend dependencies. Check with:

    ```bash
    node --version
    npm --version
    ```

### Installation

1. Duplicate the example environment file:

    ```bash
    cp .env.example .env
    ```

2. Install PHP and JavaScript dependencies:

    ```bash
    composer install
    npm install
    ```

3. Generate a new application key:

    ```bash
    php artisan key:generate
    ```

4. Run the database migrations and seed demo data:

    ```bash
    php artisan migrate --seed
    ```

5. Start the backend and the Vite frontend (run each in its own terminal):

    ```bash
    php artisan serve
    npm run dev
    ```

   Then visit the app and sign in with the demo account: `demo@cadence.test` / `password`.

## Development

### Tech Stack

- **Backend**: Laravel 12 (PHP 8.3+), Inertia.js, Fortify auth, SQLite in development.
- **Frontend**: Vue 3 (`<script setup lang="ts">`), TypeScript, Tailwind CSS v4, shadcn-vue / Reka UI, Pinia.
- **Mobile/PWA**: mobile-first UI, installable PWA via `vite-plugin-pwa`.

### Testing

```bash
php artisan test    # Pest (backend)
npm run test        # Vitest (frontend)
```

### Linting & Formatting

```bash
./vendor/bin/pint   # PHP
npm run lint        # ESLint
npm run types:check # vue-tsc
```

### Configuration

All tunables — buffers, rest windows, tolerance, intelligence thresholds, calendar, and plugins — live in `config/cadence.php`. Google Calendar sync requires `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI`; without them the null calendar driver keeps everything working.
