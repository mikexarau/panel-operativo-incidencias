import { formatDateTime } from '../../lib/format';
import IncidentFlags from './IncidentFlags';
import PriorityBadge from './PriorityBadge';
import StatusActions from './StatusActions';
import StatusBadge from './StatusBadge';

const HEADER_CELL = 'px-3 py-2 text-left text-sm font-medium text-muted';
const CELL = 'px-3 py-3 align-top';

/** Listado de escritorio. En pantallas estrechas se usan las tarjetas de IncidentCard. */
export default function IncidentTable({ incidents, onOpen, onChangeStatus }) {
    return (
        <table className="w-full border-collapse bg-surface">
            <thead>
                <tr className="border-b border-line">
                    <th className="w-2 p-0">
                        <span className="sr-only">Requiere atención</span>
                    </th>
                    <th className={HEADER_CELL}>Incidencia</th>
                    <th className={HEADER_CELL}>Prioridad</th>
                    <th className={HEADER_CELL}>Estado</th>
                    <th className={HEADER_CELL}>Responsable</th>
                    <th className={HEADER_CELL}>Fecha</th>
                    <th className={HEADER_CELL}>Acciones</th>
                </tr>
            </thead>
            <tbody>
                {incidents.map((incident) => (
                    <tr key={incident.id} className="border-b border-line last:border-b-0">
                        <td className={`w-2 p-0 ${incident.needs_attention ? 'hazard-edge' : ''}`}>
                            {incident.needs_attention && <span className="sr-only">Sí</span>}
                        </td>
                        <td className={CELL}>
                            <button
                                type="button"
                                className="cursor-pointer text-left font-semibold underline-offset-3 hover:underline"
                                onClick={() => onOpen(incident.id)}
                            >
                                {incident.title}
                            </button>
                            <p className="text-sm text-muted">Solicita {incident.requester_name}</p>
                            <IncidentFlags incident={incident} />
                        </td>
                        <td className={CELL}>
                            <PriorityBadge priority={incident.priority} label={incident.priority_label} />
                        </td>
                        <td className={CELL}>
                            <StatusBadge status={incident.status} label={incident.status_label} />
                        </td>
                        <td className={`${CELL} text-sm`}>{incident.assigned_to ?? <span className="text-muted">Sin asignar</span>}</td>
                        <td className={`${CELL} text-sm whitespace-nowrap`}>{formatDateTime(incident.created_at)}</td>
                        <td className={CELL}>
                            <div className="flex flex-wrap gap-2">
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
    );
}
