/**
 * Apariencia de estados y prioridades. Solo presentación: valores, etiquetas y
 * transiciones vienen del backend (app/Enums).
 */
export const STATUS_STYLES = {
    open: { badge: 'bg-open-tint text-open-text', mark: 'bg-open-mark', icon: 'circle' },
    in_review: { badge: 'bg-review-tint text-review-text', mark: 'bg-review-mark', icon: 'search' },
    blocked: { badge: 'bg-blocked-tint text-blocked-text', mark: 'bg-blocked-mark', icon: 'ban' },
    resolved: { badge: 'bg-resolved-tint text-resolved-text', mark: 'bg-resolved-mark', icon: 'check' },
};

export const PRIORITY_STYLES = {
    low: { level: 1, text: '' },
    medium: { level: 2, text: '' },
    high: { level: 3, text: '' },
    critical: { level: 4, text: 'text-blocked-text' },
};

const SLA_WARNING_RATIO = 0.75;

/** El medidor pasa a aviso al consumir el 75 % del plazo y a peligro al agotarlo. */
export function slaMeterStyle(sla) {
    if (sla.minutes_remaining < 0) {
        return 'bg-blocked-mark';
    }

    return sla.elapsed_ratio >= SLA_WARNING_RATIO ? 'bg-review-mark' : 'bg-open-mark';
}
