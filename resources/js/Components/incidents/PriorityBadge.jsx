const LEVELS = { low: 1, medium: 2, high: 3, critical: 4 };

/** Medidor de cuatro niveles: la prioridad se lee por forma y texto, no solo por color. */
export default function PriorityBadge({ priority, label }) {
    const level = LEVELS[priority];
    const isCritical = priority === 'critical';

    return (
        <span className={`inline-flex items-center gap-1.5 text-sm font-medium whitespace-nowrap ${isCritical ? 'text-blocked' : ''}`}>
            <span aria-hidden="true" className="flex items-end gap-0.5">
                {[1, 2, 3, 4].map((bar) => (
                    <span
                        key={bar}
                        className={`w-1 ${bar <= level ? 'bg-current' : 'bg-line'}`}
                        style={{ height: `${4 + bar * 2.5}px` }}
                    />
                ))}
            </span>
            {label}
        </span>
    );
}
