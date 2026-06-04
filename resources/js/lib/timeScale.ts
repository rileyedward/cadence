/**
 * Shared time→pixel mapping for every time-based view (doc 12) so the timeline,
 * runtime board, and comparison overlay all align. Works in minutes-from-day_start.
 */
export type TimeScale = {
    originMin: number;
    pxPerMin: number;
    y: (min: number) => number;
    height: (startMin: number, endMin: number) => number;
};

export function createTimeScale(originMin: number, pxPerMin = 1.1): TimeScale {
    return {
        originMin,
        pxPerMin,
        y: (min: number) => (min - originMin) * pxPerMin,
        height: (startMin: number, endMin: number) => Math.max(2, (endMin - startMin) * pxPerMin),
    };
}
