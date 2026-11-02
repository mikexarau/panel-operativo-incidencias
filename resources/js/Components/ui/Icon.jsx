const PATHS = {
    circle: <circle cx="12" cy="12" r="8" />,
    search: (
        <>
            <circle cx="11" cy="11" r="6.5" />
            <path d="m16 16 4.5 4.5" />
        </>
    ),
    ban: (
        <>
            <circle cx="12" cy="12" r="8.5" />
            <path d="m6 6 12 12" />
        </>
    ),
    check: <path d="m5 12.5 4.5 4.5L19 7.5" />,
    alert: (
        <>
            <path d="M12 4 2.8 20h18.4Z" />
            <path d="M12 10v5M12 17.5v.5" />
        </>
    ),
    close: <path d="M6 6l12 12M18 6 6 18" />,
};

/** Icono decorativo: el texto que lo acompana ya da el significado. */
export default function Icon({ name, className = 'size-4' }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            className={className}
        >
            {PATHS[name]}
        </svg>
    );
}
