import { useState } from 'react';

const STORAGE_KEY = 'incidents.theme';

/** El tema inicial lo fija public/theme-init.js antes de pintar; aquí solo se cambia. */
export function useTheme() {
    const [theme, setTheme] = useState(() => document.documentElement.dataset.theme ?? 'light');

    const toggleTheme = () => {
        const next = theme === 'dark' ? 'light' : 'dark';

        document.documentElement.dataset.theme = next;
        setTheme(next);

        try {
            window.localStorage.setItem(STORAGE_KEY, next);
        } catch {
            // Sin almacenamiento disponible, la elección dura lo que dure la página.
        }
    };

    return { theme, toggleTheme };
}
