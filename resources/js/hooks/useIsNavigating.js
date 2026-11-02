import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/** Indica si hay una visita en curso para atenuar el listado sin saltos de diseño. */
export function useIsNavigating() {
    const [isNavigating, setIsNavigating] = useState(false);

    useEffect(() => {
        const removeStart = router.on('start', () => setIsNavigating(true));
        const removeFinish = router.on('finish', () => setIsNavigating(false));

        return () => {
            removeStart();
            removeFinish();
        };
    }, []);

    return isNavigating;
}
