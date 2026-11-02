export default function PageHeader({ onCreate }) {
    return (
        <header className="bg-ink text-white">
            <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-6 sm:flex-row sm:items-end sm:justify-between md:px-8 md:py-8">
                <div>
                    <h1 className="font-display text-4xl leading-none font-semibold md:text-5xl">
                        Panel operativo de incidencias
                    </h1>
                    <p className="mt-2 max-w-prose text-white/80">
                        Registra, sigue y resuelve las averías de mantenimiento de las tiendas.
                    </p>
                </div>
                <button type="button" className="btn btn-primary shrink-0" onClick={onCreate}>
                    Nueva incidencia
                </button>
            </div>
        </header>
    );
}
