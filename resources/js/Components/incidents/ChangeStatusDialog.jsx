import { useForm } from '@inertiajs/react';
import Field from '../ui/Field';
import Modal from '../ui/Modal';
import OperatorField from './OperatorField';

/** Confirmacion de cualquier cambio de estado. Es el unico camino para ejecutarlos. */
export default function ChangeStatusDialog({ incident, transition, operator, onClose }) {
    const form = useForm({
        status: transition.status,
        expected_status: incident.status,
        reason: '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, actor_name: operator.name }));
        form.patch(`/incidents/${incident.id}/status`, { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Modal title={`${transition.action_label} incidencia`} onClose={onClose}>
            <form className="grid gap-3" onSubmit={submit} noValidate>
                <p>
                    <strong className="font-semibold">{incident.title}</strong> pasará de {incident.status_label} a{' '}
                    {transition.status_label}. El cambio queda registrado en el historial.
                </p>

                {form.errors.status && (
                    <p role="alert" className="field-error">
                        {form.errors.status}
                    </p>
                )}

                <Field
                    id="status-reason"
                    label={transition.requires_reason ? 'Motivo' : 'Nota (opcional)'}
                    as="textarea"
                    rows={2}
                    maxLength={1000}
                    value={form.data.reason}
                    onChange={(event) => form.setData('reason', event.target.value)}
                    error={form.errors.reason}
                />
                <OperatorField id="status-operator" operator={operator} error={form.errors.actor_name} />

                <div className="mt-2 flex justify-end gap-2">
                    <button type="button" className="btn btn-quiet" onClick={onClose}>
                        Cancelar
                    </button>
                    <button type="submit" className="btn btn-primary" disabled={form.processing}>
                        {transition.action_label}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
