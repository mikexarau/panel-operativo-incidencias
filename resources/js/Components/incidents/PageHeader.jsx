import Icon from '../ui/Icon';
import ThemeToggle from './ThemeToggle';

/** Cabecera con el nombre, la descripción, las acciones globales y la barra de filtros. */
export default function PageHeader({ onCreate, children }) {
    return (
        <header className="band">
            <div className="mx-auto max-w-[100rem] px-4 py-4 md:px-8 md:py-5">
                <div className="flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
                    <div className="flex min-w-0 items-center gap-3">
                        <span aria-hidden="true" className="hazard-edge hidden size-11 shrink-0 rounded-sm sm:block" />
                        <div className="min-w-0">
                            <h1 className="font-display text-3xl leading-none font-semibold md:text-4xl">
                                Panel operativo de incidencias
                            </h1>
                            <p className="mt-1 text-sm text-on-band-muted">
                                Registra, sigue y resuelve las averías de mantenimiento de las tiendas.
                            </p>
                        </div>
                    </div>
                    <div className="flex w-full items-center gap-2 sm:w-auto">
                        <ThemeToggle />
                        <button type="button" className="btn btn-primary grow sm:grow-0" onClick={onCreate}>
                            <Icon name="plus" />
                            Nueva incidencia
                        </button>
                    </div>
                </div>
                <div className="mt-4">{children}</div>
            </div>
        </header>
    );
}
