import { STATUS_STYLES } from '../../lib/presentation';

function Tile({ label, count, lamp, isActive, onClick }) {
    return (
        <button type="button" aria-pressed={isActive} onClick={onClick} className="tile">
            <span aria-hidden="true" className={`h-1.5 rounded-full ${isActive ? 'w-full' : 'w-8'} ${lamp}`} />
            <span className="font-display text-3xl leading-none font-semibold sm:text-4xl">{count}</span>
            <span className="text-xs leading-tight sm:text-sm">{label}</span>
        </button>
    );
}

/** Reparto de las incidencias por estado. Los indicadores de arriba hacen de leyenda. */
function DistributionBar({ statuses }) {
    const description = statuses.map((status) => `${status.label}: ${status.count}`).join(', ');

    return (
        <div role="img" aria-label={`Reparto por estado. ${description}`} className="mt-1 flex h-1 gap-0.5 px-2 sm:px-4">
            {statuses.filter((status) => status.count > 0).map((status) => (
                <span
                    key={status.value}
                    title={`${status.label}: ${status.count}`}
                    className={`min-w-1 rounded-full ${STATUS_STYLES[status.value].mark}`}
                    style={{ flexGrow: status.count }}
                />
            ))}
        </div>
    );
}

/**
 * Cuadro de mando: cada cifra es también el filtro que muestra esas incidencias. A la
 * derecha, los avisos que requieren atención se "encienden" cuando hay alguna.
 */
export default function StatusBoard({ summary, filters, onFilter }) {
    const total = summary.statuses.reduce((sum, status) => sum + status.count, 0);

    return (
        <section aria-label="Resumen de incidencias" className="border-b border-line bg-surface">
            <div className="mx-auto grid max-w-[100rem] md:px-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,2fr)] lg:divide-x lg:divide-line">
                <div className="pb-3">
                    <div className="grid grid-cols-5 divide-x divide-line">
                        <Tile
                            label="Todas"
                            count={total}
                            lamp="bg-ink"
                            isActive={!filters.status}
                            onClick={() => onFilter('status', null)}
                        />
                        {summary.statuses.map((status) => (
                            <Tile
                                key={status.value}
                                label={status.label}
                                count={status.count}
                                lamp={STATUS_STYLES[status.value].mark}
                                isActive={filters.status === status.value}
                                onClick={() => onFilter('status', status.value)}
                            />
                        ))}
                    </div>
                    <DistributionBar statuses={summary.statuses} />
                </div>
                <div className="grid grid-cols-2 divide-x divide-line border-t border-line lg:border-t-0">
                    {summary.quick_filters.map((flag) => (
                        <Tile
                            key={flag.value}
                            label={flag.label}
                            count={flag.count}
                            lamp={flag.count > 0 ? 'hazard-edge' : 'bg-line'}
                            isActive={filters.flag === flag.value}
                            onClick={() => onFilter('flag', filters.flag === flag.value ? null : flag.value)}
                        />
                    ))}
                </div>
            </div>
        </section>
    );
}
