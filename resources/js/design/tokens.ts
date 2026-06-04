/**
 * Cadence design tokens (doc 18).
 *
 * Semantic surface/text colors come from the shadcn theme variables in app.css.
 * The *primary visual signal* in Cadence is the per-intent color, which is stored
 * on each intent row and resolved at runtime — these helpers turn an intent's hex
 * color into inline styles for block fills, badges, and timeline segments.
 */

/** Minimum touch-target size in px (doc 18). */
export const TOUCH_TARGET = 44;

/** Minute grid that drag/resize snaps to; mirrors config('cadence.snap_minutes'). */
export const SNAP_MINUTES = 5;

const FALLBACK_INTENT_COLOR = '#64748b'; // slate-500

/** A solid fill + readable foreground for an intent color (timeline blocks, chips). */
export function intentFill(color?: string | null): Record<string, string> {
    const c = color || FALLBACK_INTENT_COLOR;

    return {
        backgroundColor: c,
        color: readableTextColor(c),
    };
}

/** A soft, tinted surface for an intent (cards, list rows) using a translucent fill. */
export function intentTint(color?: string | null): Record<string, string> {
    const c = color || FALLBACK_INTENT_COLOR;

    return {
        backgroundColor: hexWithAlpha(c, 0.14),
        borderColor: hexWithAlpha(c, 0.4),
        color: c,
    };
}

/** Just the accent color (dots, left borders). */
export function intentAccent(color?: string | null): string {
    return color || FALLBACK_INTENT_COLOR;
}

/** Choose black/white text for contrast against a hex background (WCAG-ish). */
export function readableTextColor(hex: string): string {
    const { r, g, b } = hexToRgb(hex);
    // Relative luminance.
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;

    return luminance > 0.6 ? '#0a0a0a' : '#ffffff';
}

function hexWithAlpha(hex: string, alpha: number): string {
    const { r, g, b } = hexToRgb(hex);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function hexToRgb(hex: string): { r: number; g: number; b: number } {
    let h = hex.replace('#', '');

    if (h.length === 3) {
        h = h
            .split('')
            .map((ch) => ch + ch)
            .join('');
    }

    const int = parseInt(h, 16);

    if (Number.isNaN(int)) {
        return { r: 100, g: 116, b: 139 };
    }

    return { r: (int >> 16) & 255, g: (int >> 8) & 255, b: int & 255 };
}
