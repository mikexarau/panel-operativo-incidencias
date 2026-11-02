import { useState } from 'react';
import ChangeStatusDialog from '../../Components/incidents/ChangeStatusDialog';
import CreateIncidentDialog from '../../Components/incidents/CreateIncidentDialog';
import FlashToast from '../../Components/incidents/FlashToast';
import IncidentCard from '../../Components/incidents/IncidentCard';
import IncidentDetailPanel from '../../Components/incidents/IncidentDetailPanel';
import IncidentFilters from '../../Components/incidents/IncidentFilters';
import IncidentTable from '../../Components/incidents/IncidentTable';
import PageHeader from '../../Components/incidents/PageHeader';
import Pagination from '../../Components/incidents/Pagination';
import StatusSummary from '../../Components/incidents/StatusSummary';
import { useIncidentFilters } from '../../hooks/useIncidentFilters';
import { useOperator } from '../../hooks/useOperator';

/**
 * Compone la pantalla. Los datos y filtros llegan por props desde el servidor; aquí
 * solo vive qué diálogo está abierto. El reparto completo se explica en el README.
 */
export default function Index({ filters, priorities, statusSummary, incidents, selectedIncident }) {
    const operator = useOperator();
    const { setFilter, clearFilters, openIncident, closeIncident } = useIncidentFilters();
    const [isCreating, setIsCreating] = useState(false);
    const [statusChange, setStatusChange] = useState(null);

    const hasFilters = Boolean(filters.status || filters.priority || filters.q);
    const requestStatusChange = (incident, transition) => setStatusChange({ incident, transition });

    return (
        <>
            <PageHeader onCreate={() => setIsCreating(true)} />
            <StatusSummary
                summary={statusSummary}
                activeStatus={filters.status}
                onSelect={(status) => setFilter('status', status)}
            />

            <main className="mx-auto max-w-7xl px-4 py-4 md:px-8 md:py-6">
                <IncidentFilters filters={filters} priorities={priorities} onChange={setFilter} />

                {incidents.data.length === 0 ? (
                    <div className="mt-4 rounded-sm bg-surface p-6">
                        <p className="font-semibold">
                            {hasFilters ? 'Ninguna incidencia coincide con estos filtros.' : 'Todavía no hay incidencias.'}
                        </p>
                        {hasFilters ? (
                            <button type="button" className="btn btn-secondary btn-sm mt-3" onClick={clearFilters}>
                                Quitar filtros
                            </button>
                        ) : (
                            <button type="button" className="btn btn-primary btn-sm mt-3" onClick={() => setIsCreating(true)}>
                                Nueva incidencia
                            </button>
                        )}
                    </div>
                ) : (
                    <>
                        <div className="mt-4 hidden lg:block">
                            <IncidentTable incidents={incidents.data} onOpen={openIncident} onChangeStatus={requestStatusChange} />
                        </div>
                        <div className="mt-4 grid gap-3 md:grid-cols-2 lg:hidden">
                            {incidents.data.map((incident) => (
                                <IncidentCard
                                    key={incident.id}
                                    incident={incident}
                                    onOpen={openIncident}
                                    onChangeStatus={requestStatusChange}
                                />
                            ))}
                        </div>
                        <Pagination links={incidents.links} meta={incidents.meta} />
                    </>
                )}
            </main>

            {selectedIncident && (
                <IncidentDetailPanel
                    key={selectedIncident.id}
                    incident={selectedIncident}
                    operator={operator}
                    onClose={closeIncident}
                    onChangeStatus={requestStatusChange}
                />
            )}

            {isCreating && (
                <CreateIncidentDialog priorities={priorities} operator={operator} onClose={() => setIsCreating(false)} />
            )}

            {statusChange && (
                <ChangeStatusDialog
                    key={`${statusChange.incident.id}-${statusChange.transition.status}`}
                    incident={statusChange.incident}
                    transition={statusChange.transition}
                    operator={operator}
                    onClose={() => setStatusChange(null)}
                />
            )}

            <FlashToast />
        </>
    );
}
