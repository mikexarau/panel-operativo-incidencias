import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Icon from '../ui/Icon';

const VISIBLE_MS = 4000;

/**
 * Confirmación breve tras una acción. Usa popover para aparecer por encima de un
 * <dialog> abierto, que de otro modo la dejaría debajo de su fondo.
 */
export default function FlashToast() {
    const [message, setMessage] = useState(null);
    const toast = useRef(null);

    useEffect(() => router.on('flash', (event) => {
        if (event.detail.flash.success) {
            setMessage(event.detail.flash.success);
        }
    }), []);

    useEffect(() => {
        if (!message) {
            return undefined;
        }

        toast.current?.showPopover();
        const timer = setTimeout(() => setMessage(null), VISIBLE_MS);

        return () => clearTimeout(timer);
    }, [message]);

    if (!message) {
        return null;
    }

    return (
        <div
            ref={toast}
            popover="manual"
            role="status"
            className="fixed top-auto right-auto bottom-4 left-4 m-0 max-w-[calc(100%-2rem)] rounded-sm bg-ink px-4 py-2 text-white"
        >
            <span className="flex items-center gap-2">
                <Icon name="check" />
                {message}
            </span>
        </div>
    );
}
