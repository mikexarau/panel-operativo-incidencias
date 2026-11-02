import { formatDateTime } from '../../lib/format';
import DataReviewFlag from './DataReviewFlag';
import PriorityBadge from './PriorityBadge';
import SlaIndicator from './SlaIndicator';
import StatusActions from './StatusActions';
import StatusBadge from './StatusBadge';

const HEADER_CELL = 'sticky top-0 z-[1] border-b border-line bg-surface px-3 py-2 text-left text-sm font-medium text-muted';
const CELL = 'px-3 py-3 align-top';

/** Listado de escritorio. En pantallas estrechas se usan las tarjetas de IncidentCard. */
export default function IncidentTable({ incidents, onOpen, onChangeStatus }) {
    return (
        <div className="panel overflow-clip">
            <table className="w-full border-collapse">
                <thead>
                    <tr>
                        <th className="sticky top-0 z-[1] w-1.5 border-b border-line bg-surface p-0">
                            <span className="sr-only">Requiere atención</span>
                        </th>
                        <th className={HEADER_CELL}>Incidencia</th>
                        <th className={`${HEADER_CELL} w-36`}>Estado y prioridad</th>
                        <th className={`${HEADER_CELL} w-40`}>Plazo y fecha</th>
                        <th className={`${HEADER_CELL} w-36`}>Responsable</th>
                        <th className={`${HEADER_CELL} w-[17rem]`}>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    {incidents.map((incident) => (
                        <tr key={incident.id} className="border-b border-line last:border-b-0 hover:bg-raised">
                            <td className={`w-1.5 p-0 ${incident.needs_attention ? 'hazard-edge' : ''}`}>
                                {incident.needs_attention && <span className="sr-only">Sí</span>}
                            </td>
                            <td className={CELL}>
                                <button
                                    type="button"
                                    className="min-h-6 cursor-pointer text-left leading-snug font-semibold underline-offset-3 hover:underline"
                                    onClick={() => onOpen(incident.id)}
                                >
                                    {incident.title}
                                </button>
                                <p className="text-sm text-muted">Solicita {incident.requester_name}</p>
                                <DataReviewFlag count={incident.open_data_issues_count} />
                            </td>
                            <td className={CELL}>
                                <div className="flex flex-col items-start gap-1.5">
                                    <StatusBadge status={incident.status} label={incident.status_label} />
                                    <PriorityBadge priority={incident.priority} label={incident.priority_label} />
                                </div>
                            </td>
                            <td className={CELL}>
                                <SlaIndicator sla={incident.sla} />
                                <p className="mt-1 text-xs text-muted tabular-nums">Creada {formatDateTime(incident.created_at)}</p>
                            </td>
                            <td className={`${CELL} text-sm`}>
                                {incident.assigned_to ?? <span className="text-muted">Sin asignar</span>}
                            </td>
                            <td className={CELL}>
                                <div className="flex flex-wrap gap-1.5">
                                    <StatusActions incident={incident} onChangeStatus={onChangeStatus} />
                                    <button type="button" className="btn btn-quiet btn-sm" onClick={() => onOpen(incident.id)}>
                                        Historial
                                    </button>
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
