const dateTime = new Intl.DateTimeFormat('es-ES', { dateStyle: 'short', timeStyle: 'short' });

export function formatDateTime(iso) {
    return dateTime.format(new Date(iso));
}
