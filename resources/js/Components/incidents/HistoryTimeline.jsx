import { formatDateTime } from '../../lib/format';

function Transition({ log }) {
    if (!log.new_label) {
        return null;
    }

    return (
        <p className="text-sm">
            {log.old_label ? `${log.old_label} → ${log.new_label}` : log.new_label}
        </p>
    );
}

/** Historial cronológico. Los logs anulados se muestran tachados: nunca desaparecen. */
export default function HistoryTimeline({ logs }) {
    if (logs.length === 0) {
        return <p className="text-sm text-muted">Esta incidencia no tiene ningún cambio registrado.</p>;
    }

    return (
        <ol className="border-l-2 border-line">
            {logs.map((log) => (
                <li key={log.id} className="relative pb-4 pl-4 last:pb-0">
                    <span aria-hidden="true" className="absolute top-1.5 -left-[5px] size-2 rounded-full bg-ink" />
                    <div className={log.voided_at ? 'text-muted line-through' : ''}>
                        <p className="font-semibold">{log.action_label}</p>
                        <Transition log={log} />
                    </div>
                    {log.voided_at && <p className="text-sm font-semibold text-review">Anulado el {formatDateTime(log.voided_at)}</p>}
                    {log.reason && <p className="mt-0.5 text-sm">{log.reason}</p>}
                    <p className="text-sm text-muted">
                        {log.user_name}, {formatDateTime(log.created_at)}
                    </p>
                </li>
            ))}
        </ol>
    );
}
