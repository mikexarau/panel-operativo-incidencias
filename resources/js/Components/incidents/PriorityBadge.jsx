import { PRIORITY_STYLES } from '../../lib/presentation';

/** Medidor de cuatro niveles: la prioridad se lee por forma y texto, no solo por color. */
export default function PriorityBadge({ priority, label }) {
    const style = PRIORITY_STYLES[priority];

    return (
        <span className={`inline-flex items-center gap-1.5 text-sm font-medium whitespace-nowrap ${style.text}`}>
            <span aria-hidden="true" className="flex items-end gap-0.5">
                {[1, 2, 3, 4].map((bar) => (
                    <span
                        key={bar}
                        className={`w-1 rounded-t-[1px] ${bar <= style.level ? 'bg-current' : 'bg-line'}`}
                        style={{ height: `${4 + bar * 2.5}px` }}
                    />
                ))}
            </span>
            {label}
        </span>
    );
}
