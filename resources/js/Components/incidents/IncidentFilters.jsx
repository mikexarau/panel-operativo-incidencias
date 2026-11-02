import { useEffect, useRef, useState } from 'react';
import Field from '../ui/Field';
import Icon from '../ui/Icon';

const SEARCH_DELAY_MS = 300;
const DEFAULT_SORT = 'recent';

/**
 * Filtros que afectan a todos los indicadores y al listado. El texto vive aquí mientras
 * se escribe y se envía a la URL cuando el usuario hace una pausa.
 */
export default function IncidentFilters({ filters, priorities, sorts, hasFilters, onChange, onClear }) {
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
    }, [search, onChange]);

    // Refleja cambios externos, como "Quitar filtros". La respuesta a lo que se acaba de
    // enviar no cuenta: pisaría lo escrito mientras tanto. Se compara sin espacios
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
            className="grid grid-cols-2 gap-2 lg:grid-cols-[minmax(0,1fr)_12rem_11rem_auto]"
            onSubmit={(event) => event.preventDefault()}
        >
            <div className="relative col-span-2 lg:col-span-1">
                <span className="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-muted">
                    <Icon name="search" />
                </span>
                <Field
                    id="filter-q"
                    label="Buscar"
                    hideLabel
                    type="search"
                    className="pl-8"
                    placeholder="Buscar por título, descripción o persona"
                    maxLength={100}
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                />
            </div>
            <Field
                id="filter-priority"
                label="Prioridad"
                hideLabel
                as="select"
                value={filters.priority ?? ''}
                onChange={(event) => onChange('priority', event.target.value)}
            >
                <option value="">Prioridad: todas</option>
                {priorities.map((priority) => (
                    <option key={priority.value} value={priority.value}>
                        {priority.label}
                    </option>
                ))}
            </Field>
            <Field
                id="filter-sort"
                label="Orden"
                hideLabel
                as="select"
                value={filters.sort}
                onChange={(event) => onChange('sort', event.target.value === DEFAULT_SORT ? null : event.target.value)}
            >
                {sorts.map((sort) => (
                    <option key={sort.value} value={sort.value}>
                        {sort.label}
                    </option>
                ))}
            </Field>
            {hasFilters && (
                <button type="button" className="btn btn-on-band col-span-2 lg:col-span-1" onClick={onClear}>
                    <Icon name="close" />
                    Quitar filtros
                </button>
            )}
        </form>
    );
}
