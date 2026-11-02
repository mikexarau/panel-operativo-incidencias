import { useState } from 'react';
import ActivityFeed from '../../Components/incidents/ActivityFeed';
import ChangeStatusDialog from '../../Components/incidents/ChangeStatusDialog';
import CreateIncidentDialog from '../../Components/incidents/CreateIncidentDialog';
import FlashToast from '../../Components/incidents/FlashToast';
import IncidentCard from '../../Components/incidents/IncidentCard';
import IncidentDetailPanel from '../../Components/incidents/IncidentDetailPanel';
import IncidentFilters from '../../Components/incidents/IncidentFilters';
import IncidentTable from '../../Components/incidents/IncidentTable';
import PageHeader from '../../Components/incidents/PageHeader';
import Pagination from '../../Components/incidents/Pagination';
import PriorityLoad from '../../Components/incidents/PriorityLoad';
import StatusBoard from '../../Components/incidents/StatusBoard';
import { incidentNavigation } from '../../lib/incidentNavigation';
import { useIsNavigating } from '../../hooks/useIsNavigating';
import { useOperator } from '../../hooks/useOperator';

/**
 * Compone la pantalla. Los datos y filtros llegan por props desde el servidor; aquí
 * solo vive qué diálogo está abierto. El reparto completo se explica en el README.
 */
export default function Index({ filters, priorities, sorts, summary, priorityLoad, recentActivity, incidents, selectedIncident }) {
    const operator = useOperator();
    const isNavigating = useIsNavigating();
    const { setFilter, clearFilters, openIncident, closeIncident } = incidentNavigation;
    const [isCreating, setIsCreating] = useState(false);
    const [statusChange, setStatusChange] = useState(null);

    const hasFilters = Boolean(filters.status || filters.priority || filters.q || filters.flag);
    const requestStatusChange = (incident, transition) => setStatusChange({ incident, transition });
    const total = incidents.meta.total;

    return (
        <>
            <PageHeader onCreate={() => setIsCreating(true)}>
                <IncidentFilters
                    filters={filters}
                    priorities={priorities}
                    sorts={sorts}
                    hasFilters={hasFilters}
                    onChange={setFilter}
                    onClear={clearFilters}
                />
            </PageHeader>
            <StatusBoard summary={summary} filters={filters} onFilter={setFilter} />

            <div className="mx-auto grid max-w-[100rem] gap-4 px-4 py-4 md:px-8 md:py-6 xl:grid-cols-[minmax(0,1fr)_19rem] xl:items-start">
                <main
                    aria-busy={isNavigating}
                    className={`min-w-0 transition-opacity delay-150 duration-150 ${isNavigating ? 'opacity-60' : ''}`}
                >
                    <h2 aria-live="polite" className="panel-title mb-2">
                        {total === 1 ? '1 incidencia' : `${total} incidencias`}
                    </h2>

                    {incidents.data.length === 0 ? (
                        <div className="panel p-6">
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
                            <div className="hidden lg:block">
                                <IncidentTable incidents={incidents.data} onOpen={openIncident} onChangeStatus={requestStatusChange} />
                            </div>
                            <div className="grid gap-3 md:grid-cols-2 lg:hidden">
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

                <aside aria-label="Indicadores" className="grid gap-4 md:grid-cols-2 xl:grid-cols-1">
                    <PriorityLoad
                        load={priorityLoad}
                        activePriority={filters.priority}
                        onSelect={(priority) => setFilter('priority', priority)}
                    />
                    <ActivityFeed activity={recentActivity} onOpen={openIncident} />
                </aside>
            </div>

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
