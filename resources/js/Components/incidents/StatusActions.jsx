/** Botones de las transiciones que el backend permite desde el estado actual. */
export default function StatusActions({ incident, onChangeStatus }) {
    return incident.allowed_transitions.map((transition) => (
        <button
            key={transition.status}
            type="button"
            className="btn btn-secondary btn-sm"
            onClick={() => onChangeStatus(incident, transition)}
        >
            {transition.action_label}
        </button>
    ));
}
