import Field from '../ui/Field';

/** Quién firma la acción en el historial. Compartido por todos los formularios. */
export default function OperatorField({ id, operator, error }) {
    return (
        <Field
            id={id}
            label="Tu nombre"
            autoComplete="name"
            value={operator.name}
            onChange={(event) => operator.setName(event.target.value)}
            error={error}
        />
    );
}
