import { router } from '@inertiajs/react';

/**
 * Unico punto que navega dentro del panel. La URL guarda los filtros y la incidencia
 * abierta, y cada cambio pide al servidor solo las props afectadas.
 */
function visit(changes, only) {
    const params = new URLSearchParams(window.location.search);

    Object.entries(changes).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        } else {
            params.delete(key);
        }
    });

    router.get(`/incidents?${params}`, {}, { only, preserveState: true, preserveScroll: true });
}

const LIST_PROPS = ['incidents', 'statusSummary', 'filters'];
const DETAIL_PROPS = ['selectedIncident'];

export function useIncidentFilters() {
    return {
        setFilter: (key, value) => visit({ [key]: value, page: null }, LIST_PROPS),
        clearFilters: () => visit({ status: null, priority: null, q: null, page: null }, LIST_PROPS),
        openIncident: (id) => visit({ incident: id }, DETAIL_PROPS),
        closeIncident: () => visit({ incident: null }, DETAIL_PROPS),
    };
}
