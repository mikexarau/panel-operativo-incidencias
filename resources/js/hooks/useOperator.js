import { useState } from 'react';

const STORAGE_KEY = 'incidents.operator';

/**
 * Nombre de quien opera el panel. Sin autenticacion es autodeclarado: se recuerda en
 * este navegador por comodidad y el backend lo valida en cada escritura.
 */
export function useOperator() {
    const [name, setStoredName] = useState(() => {
        try {
            return window.localStorage.getItem(STORAGE_KEY) ?? '';
        } catch {
            return '';
        }
    });

    const setName = (value) => {
        setStoredName(value);

        try {
            window.localStorage.setItem(STORAGE_KEY, value);
        } catch {
            // Sin almacenamiento disponible, el nombre dura lo que dure la pagina.
        }
    };

    return { name, setName };
}
