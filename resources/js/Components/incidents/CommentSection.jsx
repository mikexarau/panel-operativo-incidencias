import { useForm } from '@inertiajs/react';
import { formatDateTime } from '../../lib/format';
import Field from '../ui/Field';
import OperatorField from './OperatorField';

export default function CommentSection({ incident, operator }) {
    const form = useForm({ body: '' });

    const submit = (event) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, actor_name: operator.name }));
        form.post(`/incidents/${incident.id}/comments`, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <>
            {incident.comments.length === 0 ? (
                <p className="text-sm text-muted">Todavía no hay comentarios. Añade el primero abajo.</p>
            ) : (
                <ul className="grid gap-3">
                    {incident.comments.map((comment) => (
                        <li key={comment.id} className="border-l-2 border-line pl-3">
                            <p className="whitespace-pre-line">{comment.body}</p>
                            <p className="text-sm text-muted">
                                {comment.author_name}, {formatDateTime(comment.created_at)}
                            </p>
                        </li>
                    ))}
                </ul>
            )}

            <form className="mt-4 grid gap-3" onSubmit={submit} noValidate>
                <Field
                    id="comment-body"
                    label="Comentario"
                    as="textarea"
                    rows={2}
                    maxLength={2000}
                    value={form.data.body}
                    onChange={(event) => form.setData('body', event.target.value)}
                    error={form.errors.body}
                />
                <OperatorField id="comment-operator" operator={operator} error={form.errors.actor_name} />
                <div>
                    <button type="submit" className="btn btn-secondary" disabled={form.processing}>
                        Añadir comentario
                    </button>
                </div>
            </form>
        </>
    );
}
