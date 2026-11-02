import Icon from '../ui/Icon';

export default function DataReviewFlag({ count }) {
    if (!count) {
        return null;
    }

    return (
        <span className="inline-flex items-center gap-1 text-sm font-semibold whitespace-nowrap text-review-text">
            <Icon name="alert" className="size-3.5" />
            Revisión de datos
        </span>
    );
}
