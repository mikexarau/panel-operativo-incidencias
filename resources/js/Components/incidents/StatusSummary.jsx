import { STATUS_STYLES } from '../../lib/statusStyles';

function Segment({ label, count, isActive, activeBar, onClick }) {
    return (
        <button
            type="button"
            aria-pressed={isActive}
            onClick={onClick}
            className={`flex cursor-pointer flex-col items-start border-b-4 px-2 py-2 text-left sm:px-4 sm:py-3 ${
                isActive ? `bg-surface ${activeBar}` : 'border-transparent hover:bg-surface'
            }`}
        >
            <span className="font-display text-3xl leading-none font-semibold sm:text-4xl">{count}</span>
            <span className="mt-1 text-xs leading-tight sm:text-sm">{label}</span>
        </button>
    );
}

/** Contadores por estado que funcionan a la vez como filtro rapido. */
export default function StatusSummary({ summary, activeStatus, onSelect }) {
    const total = summary.reduce((sum, status) => sum + status.count, 0);

    return (
        <nav aria-label="Filtrar por estado" className="border-b border-line bg-ground">
            <div className="mx-auto grid max-w-7xl grid-cols-5 divide-x divide-line md:px-8">
                <Segment
                    label="Todas"
                    count={total}
                    isActive={!activeStatus}
                    activeBar="border-ink"
                    onClick={() => onSelect(null)}
                />
                {summary.map((status) => (
                    <Segment
                        key={status.value}
                        label={status.label}
                        count={status.count}
                        isActive={activeStatus === status.value}
                        activeBar={STATUS_STYLES[status.value].bar}
                        onClick={() => onSelect(status.value)}
                    />
                ))}
            </div>
        </nav>
    );
}
