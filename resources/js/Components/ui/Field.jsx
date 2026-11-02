/**
 * Etiqueta, control y error de validacion enlazados. `as` admite input, textarea o select.
 */
export default function Field({ id, label, error, as: Control = 'input', ...controlProps }) {
    return (
        <div>
            <label htmlFor={id} className="field-label">
                {label}
            </label>
            <Control
                id={id}
                className="field-control"
                aria-invalid={error ? 'true' : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
                {...controlProps}
            />
            {error && (
                <p id={`${id}-error`} className="field-error">
                    {error}
                </p>
            )}
        </div>
    );
}
