/**
 * Display-side time helpers (doc 13). These are for *rendering* only — all
 * scheduling math is done server-side in minutes-from-day_start (doc 07).
 */

/** "HH:MM" -> minutes since 00:00 (0..1439). */
export function hhmmToMinutes(hhmm: string): number {
    const [h, m] = hhmm.split(':').map((n) => parseInt(n, 10));

    return (h || 0) * 60 + (m || 0);
}

/** minutes (may exceed 1440 for past-midnight) -> "HH:MM" on a 24h clock. */
export function minutesToHhmm(minutes: number): string {
    const norm = ((minutes % 1440) + 1440) % 1440;
    const h = Math.floor(norm / 60);
    const m = norm % 60;

    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

/** Friendly duration, e.g. 95 -> "1h 35m". */
export function formatDuration(minutes: number): string {
    const h = Math.floor(minutes / 60);
    const m = Math.round(minutes % 60);

    if (h && m) {
        return `${h}h ${m}m`;
    }

    if (h) {
        return `${h}h`;
    }

    return `${m}m`;
}

/**
 * Convert a clock "HH:MM" into minutes-from-day_start, applying the +1440 wrap
 * rule for times before the logical day boundary (doc 07). Used only to position
 * events for rendering; the server remains authoritative.
 */
export function minutesFromDayStart(hhmm: string, dayStart: string): number {
    const t = hhmmToMinutes(hhmm);
    const start = hhmmToMinutes(dayStart);

    return t < start ? t - start + 1440 : t - start;
}
