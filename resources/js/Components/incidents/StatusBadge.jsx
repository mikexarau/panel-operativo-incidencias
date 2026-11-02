import { STATUS_STYLES } from '../../lib/statusStyles';
import Icon from '../ui/Icon';

export default function StatusBadge({ status, label }) {
    const style = STATUS_STYLES[status];

    return (
        <span className={`inline-flex items-center gap-1.5 rounded-sm px-2 py-0.5 text-sm font-semibold whitespace-nowrap ${style.badge}`}>
            <Icon name={style.icon} className="size-3.5" />
            {label}
        </span>
    );
}
