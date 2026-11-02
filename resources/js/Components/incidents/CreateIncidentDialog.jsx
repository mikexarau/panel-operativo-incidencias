import { useForm } from '@inertiajs/react';
import Field from '../ui/Field';
import Modal from '../ui/Modal';
import OperatorField from './OperatorField';

export default function CreateIncidentDialog({ priorities, operator, onClose }) {
    const form = useForm({
        title: '',
        description: '',
        priority: 'medium',
        requester_name: '',
        assigned_to: '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, actor_name: operator.name }));
        form.post('/incidents', { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Modal title="Nueva incidencia" onClose={onClose}>
            <form className="grid gap-3" onSubmit={submit} noValidate>
                <Field
                    id="create-title"
                    label="Título"
                    maxLength={160}
                    value={form.data.title}
                    onChange={(event) => form.setData('title', event.target.value)}
                    error={form.errors.title}
                />
                <Field
                    id="create-description"
                    label="Descripción"
                    as="textarea"
                    rows={3}
                    value={form.data.description}
                    onChange={(event) => form.setData('description', event.target.value)}
                    error={form.errors.description}
                />
                <Field
                    id="create-priority"
                    label="Prioridad"
                    as="select"
                    value={form.data.priority}
                    onChange={(event) => form.setData('priority', event.target.value)}
                    error={form.errors.priority}
                >
                    {priorities.map((priority) => (
                        <option key={priority.value} value={priority.value}>
                            {priority.label}
                        </option>
                    ))}
                </Field>
                <div className="grid gap-3 sm:grid-cols-2">
                    <Field
                        id="create-requester"
                        label="Solicitante"
                        maxLength={100}
                        value={form.data.requester_name}
                        onChange={(event) => form.setData('requester_name', event.target.value)}
                        error={form.errors.requester_name}
                    />
                    <Field
                        id="create-assigned"
                        label="Responsable (opcional)"
                        maxLength={100}
                        value={form.data.assigned_to}
                        onChange={(event) => form.setData('assigned_to', event.target.value)}
                        error={form.errors.assigned_to}
                    />
                </div>
                <OperatorField id="create-operator" operator={operator} error={form.errors.actor_name} />

                <div className="mt-2 flex justify-end gap-2">
                    <button type="button" className="btn btn-quiet" onClick={onClose}>
                        Cancelar
                    </button>
                    <button type="submit" className="btn btn-primary" disabled={form.processing}>
                        Crear incidencia
                    </button>
                </div>
            </form>
        </Modal>
    );
}
