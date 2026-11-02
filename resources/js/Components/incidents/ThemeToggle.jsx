import { useTheme } from '../../hooks/useTheme';
import Icon from '../ui/Icon';

export default function ThemeToggle() {
    const { theme, toggleTheme } = useTheme();
    const isDark = theme === 'dark';

    return (
        <button
            type="button"
            className="btn btn-on-band px-2.5"
            onClick={toggleTheme}
            aria-label={isDark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'}
            title={isDark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'}
        >
            <Icon name={isDark ? 'sun' : 'moon'} className="size-5" />
        </button>
    );
}
