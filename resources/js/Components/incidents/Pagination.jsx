import { Link } from '@inertiajs/react';

const LINK_OPTIONS = {
    only: ['incidents', 'statusSummary', 'filters'],
    preserveState: true,
};

export default function Pagination({ links, meta }) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <nav aria-label="Paginación" className="flex items-center justify-between gap-4 py-3">
            {links.prev ? (
                <Link href={links.prev} className="btn btn-secondary btn-sm" {...LINK_OPTIONS}>
                    Anterior
                </Link>
            ) : (
                <span />
            )}
            <span className="text-sm text-muted">
                Página {meta.current_page} de {meta.last_page}
            </span>
            {links.next ? (
                <Link href={links.next} className="btn btn-secondary btn-sm" {...LINK_OPTIONS}>
                    Siguiente
                </Link>
            ) : (
                <span />
            )}
        </nav>
    );
}
