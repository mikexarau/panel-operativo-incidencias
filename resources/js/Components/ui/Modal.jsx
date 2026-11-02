import { useEffect, useId, useRef } from 'react';
import Icon from './Icon';

/**
 * Envoltorio de <dialog> nativo: aporta foco atrapado, Esc y fondo inerte sin codigo
 * propio. El padre decide si existe montandolo o no.
 */
export default function Modal({ title, onClose, variant = 'modal', children }) {
    const dialog = useRef(null);
    const opener = useRef(document.activeElement);
    const titleId = useId();

    useEffect(() => {
        if (!dialog.current.open) {
            dialog.current.showModal();
        }

        return () => opener.current?.focus?.();
    }, []);

    return (
        <dialog ref={dialog} className={variant} aria-labelledby={titleId} onClose={onClose}>
            <div className="flex items-start justify-between gap-4 border-b border-line px-4 py-3 md:px-6">
                <h2 id={titleId} className="font-display text-2xl leading-tight font-semibold">
                    {title}
                </h2>
                <button type="button" className="btn btn-quiet btn-sm" onClick={onClose} aria-label="Cerrar">
                    <Icon name="close" />
                </button>
            </div>
            <div className="px-4 py-4 md:px-6">{children}</div>
        </dialog>
    );
}
