/** Incidencias sin resolver por prioridad. Pulsar una barra filtra por esa prioridad. */
export default function PriorityLoad({ load, activePriority, onSelect }) {
    const max = Math.max(1, ...load.map((priority) => priority.count));

    return (
        <section aria-labelledby="priority-load-title" className="panel p-4">
            <h2 id="priority-load-title" className="panel-title">
                Sin resolver por prioridad
            </h2>
            <ul className="mt-2 grid gap-0.5">
                {load.map((priority) => (
                    <li key={priority.value}>
                        <button
                            type="button"
                            aria-pressed={activePriority === priority.value}
                            onClick={() => onSelect(activePriority === priority.value ? null : priority.value)}
                            className="grid w-full cursor-pointer grid-cols-[3.75rem_minmax(0,1fr)_1.5rem] items-center gap-2 rounded-sm px-1 py-1.5 text-left text-sm hover:bg-raised aria-pressed:bg-raised aria-pressed:font-semibold"
                        >
                            <span>{priority.label}</span>
                            <span aria-hidden="true" className="block h-3">
                                <span
                                    className="block h-full rounded-r bg-ink"
                                    style={{ width: `${(priority.count / max) * 100}%` }}
                                />
                            </span>
                            <span className="text-right font-semibold tabular-nums">{priority.count}</span>
                        </button>
                    </li>
                ))}
            </ul>
        </section>
    );
}
