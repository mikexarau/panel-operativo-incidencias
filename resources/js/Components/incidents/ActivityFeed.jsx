import { formatRelative, formatTransition } from '../../lib/format';

/** Últimos cambios de todo el sistema: la trazabilidad a la vista, sin abrir cada incidencia. */
export default function ActivityFeed({ activity, onOpen }) {
    return (
        <section aria-labelledby="activity-title" className="panel p-4">
            <h2 id="activity-title" className="panel-title">
                Actividad reciente
            </h2>
            {activity.length === 0 ? (
                <p className="mt-2 text-sm text-muted">Los cambios aparecerán aquí en cuanto se registre alguno.</p>
            ) : (
                <ol className="mt-3 grid gap-3">
                    {activity.map((entry) => (
                        <li key={entry.id} className="border-l-2 border-line pl-3 text-sm">
                            <button
                                type="button"
                                className="min-h-6 cursor-pointer text-left font-semibold underline-offset-3 hover:underline"
                                onClick={() => onOpen(entry.incident_id)}
                            >
                                {entry.incident_title}
                            </button>
                            <p>
                                {entry.action_label}
                                {entry.new_label && `: ${formatTransition(entry.old_label, entry.new_label)}`}
                            </p>
                            <p className="text-muted">
                                {entry.user_name}, {formatRelative(entry.created_at)}
                            </p>
                        </li>
                    ))}
                </ol>
            )}
        </section>
    );
}
