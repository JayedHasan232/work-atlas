import { useTranslation } from '@/lib/i18n';
import type { PublicField } from '@/types/occupations';

/** One field's value as a reader sees it, or why there is none. */
export default function FieldValue({ field }: { field: PublicField }) {
    const { t, formatNumber } = useTranslation();
    const value = field.value;

    if (field.withheld) {
        return (
            <p className="text-muted-foreground">
                {t('occupations.show.withheld')}
            </p>
        );
    }

    if (value === null) {
        return (
            <p className="text-muted-foreground">
                {t('occupations.show.not_filled')}
            </p>
        );
    }

    switch (value.kind) {
        case 'unknown':
            return (
                <p className="text-muted-foreground">
                    {t('occupations.show.unknown')}
                </p>
            );
        case 'scale':
            return (
                <p>
                    <span className="font-medium">
                        {t('occupations.show.scale', {
                            point: formatNumber(value.point),
                        })}
                    </span>
                    {value.anchor !== null && <> · {value.anchor}</>}
                </p>
            );
        case 'options':
            return <p>{value.labels.join(', ')}</p>;
        case 'boolean':
            return (
                <p>
                    {t(
                        value.value
                            ? 'occupations.show.yes'
                            : 'occupations.show.no',
                    )}
                </p>
            );
        case 'text':
            return <p lang={field.valueLocale ?? undefined}>{value.text}</p>;
    }
}
