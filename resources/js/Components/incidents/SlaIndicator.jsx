import { formatDuration } from '../../lib/format';
import { slaMeterStyle } from '../../lib/presentation';

/** Tiempo restante del plazo: el texto da el dato y el medidor, la lectura de un vistazo. */
export default function SlaIndicator({ sla }) {
    if (!sla) {
        return <p className="text-sm text-muted">Resuelta, sin plazo</p>;
    }

    const isOverdue = sla.minutes_remaining < 0;

    return (
        <div>
            <p className={`text-sm font-medium tabular-nums ${isOverdue ? 'text-blocked-text' : ''}`}>
                {isOverdue ? `Vencida hace ${formatDuration(sla.minutes_remaining)}` : `Quedan ${formatDuration(sla.minutes_remaining)}`}
            </p>
            <div aria-hidden="true" className="mt-1 h-1.5 overflow-hidden rounded-full bg-line">
                <div
                    className={`h-full rounded-full ${slaMeterStyle(sla)}`}
                    style={{ width: `${Math.min(sla.elapsed_ratio, 1) * 100}%` }}
                />
            </div>
        </div>
    );
}
