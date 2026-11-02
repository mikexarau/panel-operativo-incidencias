/**
 * Apariencia de cada estado. Solo presentacion: valores, etiquetas y transiciones
 * vienen del backend (app/Enums/IncidentStatus.php).
 */
export const STATUS_STYLES = {
    open: { badge: 'bg-open-tint text-open', bar: 'border-open', icon: 'circle' },
    in_review: { badge: 'bg-review-tint text-review', bar: 'border-review', icon: 'search' },
    blocked: { badge: 'bg-blocked-tint text-blocked', bar: 'border-blocked', icon: 'ban' },
    resolved: { badge: 'bg-resolved-tint text-resolved', bar: 'border-resolved', icon: 'check' },
};
