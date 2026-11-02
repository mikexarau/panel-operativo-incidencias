import Icon from '../ui/Icon';

function Flag({ children, className }) {
    return (
        <span className={`inline-flex items-center gap-1 text-sm font-semibold whitespace-nowrap ${className}`}>
            <Icon name="alert" className="size-3.5" />
            {children}
        </span>
    );
}

/** Avisos en texto que acompañan a la banda de peligro de la fila. */
export default function IncidentFlags({ incident }) {
    if (!incident.is_overdue && !incident.open_data_issues_count) {
        return null;
    }

    return (
        <span className="flex flex-wrap gap-x-3 gap-y-0.5">
            {incident.is_overdue && <Flag className="text-blocked">Vencida</Flag>}
            {incident.open_data_issues_count > 0 && <Flag className="text-review">Revisión de datos</Flag>}
        </span>
    );
}
