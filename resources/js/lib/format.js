const dateTime = new Intl.DateTimeFormat('es-ES', { dateStyle: 'short', timeStyle: 'short' });
const relativeTime = new Intl.RelativeTimeFormat('es-ES', { numeric: 'auto' });

const MINUTE_MS = 60_000;
const MINUTES_PER_HOUR = 60;
const MINUTES_PER_DAY = 1440;
const HOURS_BEFORE_DAYS = 48;

export function formatDateTime(iso) {
    return dateTime.format(new Date(iso));
}

export function formatDuration(minutes) {
    const total = Math.abs(minutes);

    if (total < MINUTES_PER_HOUR) {
        return `${total} min`;
    }

    return total < HOURS_BEFORE_DAYS * MINUTES_PER_HOUR
        ? `${Math.round(total / MINUTES_PER_HOUR)} h`
        : `${Math.round(total / MINUTES_PER_DAY)} d`;
}

export function formatRelative(iso) {
    const minutes = Math.round((new Date(iso).getTime() - Date.now()) / MINUTE_MS);
    const total = Math.abs(minutes);

    if (total < MINUTES_PER_HOUR) {
        return relativeTime.format(minutes, 'minute');
    }

    return total < HOURS_BEFORE_DAYS * MINUTES_PER_HOUR
        ? relativeTime.format(Math.round(minutes / MINUTES_PER_HOUR), 'hour')
        : relativeTime.format(Math.round(minutes / MINUTES_PER_DAY), 'day');
}

export function formatTransition(oldLabel, newLabel) {
    return oldLabel ? `${oldLabel} → ${newLabel}` : newLabel;
}
