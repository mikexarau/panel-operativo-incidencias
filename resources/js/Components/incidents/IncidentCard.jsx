import { formatDateTime } from '../../lib/format';
import IncidentFlags from './IncidentFlags';
import PriorityBadge from './PriorityBadge';
import StatusActions from './StatusActions';
import StatusBadge from './StatusBadge';

/** Version movil de una fila del listado. */
export default function IncidentCard({ incident, onOpen, onChangeStatus }) {
    return (
        <article className="relative overflow-hidden rounded-sm bg-surface py-3 pr-3 pl-5">
            {incident.needs_attention && <span aria-hidden="true" className="hazard-edge absolute inset-y-0 left-0 w-2" />}

            <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                <StatusBadge status={incident.status} label={incident.status_label} />
                <PriorityBadge priority={incident.priority} label={incident.priority_label} />
            </div>

            <h2 className="mt-2 leading-snug font-semibold">
                <button
                    type="button"
                    className="cursor-pointer text-left underline-offset-3 hover:underline"
                    onClick={() => onOpen(incident.id)}
                >
                    {incident.title}
                </button>
            </h2>
            <IncidentFlags incident={incident} />

            <dl className="mt-2 grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 text-sm">
                <dt className="text-muted">Solicita</dt>
                <dd>{incident.requester_name}</dd>
                <dt className="text-muted">Responsable</dt>
                <dd>{incident.assigned_to ?? 'Sin asignar'}</dd>
                <dt className="text-muted">Fecha</dt>
                <dd>{formatDateTime(incident.created_at)}</dd>
            </dl>

            <div className="mt-3 flex flex-wrap gap-2">
                <StatusActions incident={incident} onChangeStatus={onChangeStatus} />
                <button type="button" className="btn btn-quiet btn-sm" onClick={() => onOpen(incident.id)}>
                    Historial
                </button>
            </div>
        </article>
    );
}
