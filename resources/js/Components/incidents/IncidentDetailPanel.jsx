import { formatDateTime } from '../../lib/format';
import Icon from '../ui/Icon';
import Modal from '../ui/Modal';
import CommentSection from './CommentSection';
import HistoryTimeline from './HistoryTimeline';
import IncidentFlags from './IncidentFlags';
import PriorityBadge from './PriorityBadge';
import StatusActions from './StatusActions';
import StatusBadge from './StatusBadge';

function DataIssueNotice({ issue }) {
    return (
        <div className="flex gap-2 rounded-sm bg-review-tint p-3 text-sm text-review">
            <Icon name="alert" className="mt-0.5 size-4 shrink-0" />
            <div>
                <p className="font-semibold">
                    Revisión de datos #{issue.id}: {issue.rule_label}
                </p>
                <p>{issue.proposed_action}</p>
            </div>
        </div>
    );
}

export default function IncidentDetailPanel({ incident, operator, onClose, onChangeStatus }) {
    return (
        <Modal title={incident.title} variant="drawer" onClose={onClose}>
            <div className="grid gap-6">
                <section aria-label="Resumen" className="grid gap-3">
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <StatusBadge status={incident.status} label={incident.status_label} />
                        <PriorityBadge priority={incident.priority} label={incident.priority_label} />
                        <IncidentFlags incident={incident} />
                    </div>
                    <p className="whitespace-pre-line">{incident.description}</p>
                    <dl className="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 text-sm">
                        <dt className="text-muted">Solicita</dt>
                        <dd>{incident.requester_name}</dd>
                        <dt className="text-muted">Responsable</dt>
                        <dd>{incident.assigned_to ?? 'Sin asignar'}</dd>
                        <dt className="text-muted">Fecha</dt>
                        <dd>{formatDateTime(incident.created_at)}</dd>
                    </dl>
                    <div className="flex flex-wrap gap-2">
                        <StatusActions incident={incident} onChangeStatus={onChangeStatus} />
                    </div>
                    {incident.open_data_issues.map((issue) => (
                        <DataIssueNotice key={issue.id} issue={issue} />
                    ))}
                </section>

                <section aria-labelledby="history-title">
                    <h3 id="history-title" className="mb-2 font-display text-xl font-semibold">
                        Historial de cambios
                    </h3>
                    <HistoryTimeline logs={incident.logs} />
                </section>

                <section aria-labelledby="comments-title">
                    <h3 id="comments-title" className="mb-2 font-display text-xl font-semibold">
                        Comentarios
                    </h3>
                    <CommentSection incident={incident} operator={operator} />
                </section>
            </div>
        </Modal>
    );
}
