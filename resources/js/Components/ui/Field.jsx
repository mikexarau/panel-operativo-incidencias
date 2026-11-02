/**
 * Etiqueta, control y error de validación enlazados. `as` admite input, textarea o
 * select. `hideLabel` la oculta a la vista, pero no a los lectores de pantalla.
 */
export default function Field({ id, label, error, hideLabel = false, className = '', as: Control = 'input', ...controlProps }) {
    return (
        <div>
            <label htmlFor={id} className={hideLabel ? 'sr-only' : 'field-label'}>
                {label}
            </label>
            <Control
                id={id}
                className={`field-control ${className}`}
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
