import { useEffect, useRef, useState } from 'react';
import Field from '../ui/Field';

const SEARCH_DELAY_MS = 300;

/** El texto vive aqui mientras se escribe; se envia a la URL cuando el usuario hace una pausa. */
export default function IncidentFilters({ filters, priorities, onChange }) {
    const [search, setSearch] = useState(filters.q ?? '');
    const lastSent = useRef(filters.q ?? '');

    useEffect(() => {
        if (search === lastSent.current) {
            return undefined;
        }

        const timer = setTimeout(() => {
            lastSent.current = search;
            onChange('q', search);
        }, SEARCH_DELAY_MS);

        return () => clearTimeout(timer);
    }, [search]);

    // Refleja cambios externos, como "Quitar filtros". La respuesta a lo que se acaba de
    // enviar no cuenta: pisaria lo escrito mientras tanto. Se compara sin espacios
    // laterales porque el servidor los recorta.
    useEffect(() => {
        const external = filters.q ?? '';

        if (external !== lastSent.current.trim()) {
            lastSent.current = external;
            setSearch(external);
        }
    }, [filters.q]);

    return (
        <form
            role="search"
            className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem]"
            onSubmit={(event) => event.preventDefault()}
        >
            <Field
                id="filter-q"
                label="Buscar"
                type="search"
                placeholder="Título, descripción, solicitante o responsable"
                maxLength={100}
                value={search}
                onChange={(event) => setSearch(event.target.value)}
            />
            <Field
                id="filter-priority"
                label="Prioridad"
                as="select"
                value={filters.priority ?? ''}
                onChange={(event) => onChange('priority', event.target.value)}
            >
                <option value="">Todas</option>
                {priorities.map((priority) => (
                    <option key={priority.value} value={priority.value}>
                        {priority.label}
                    </option>
                ))}
            </Field>
        </form>
    );
}
