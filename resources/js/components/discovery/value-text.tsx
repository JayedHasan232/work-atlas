import { useTranslation } from '@/lib/i18n';
import type { DisplayValue } from '@/types/occupations';

/** A field value as one short line of text, for the guided filter and compare tables. */
export default function ValueText({ value }: { value: DisplayValue | null }) {
    const { t, formatNumber } = useTranslation();

    if (value === null || value.kind === 'unknown') {
        return (
            <span className="text-muted-foreground">
                {t('guide.compare.not_known')}
            </span>
        );
    }

    switch (value.kind) {
        case 'scale':
            return (
                <span>
                    {t('occupations.show.scale', {
                        point: formatNumber(value.point),
                    })}
                    {value.anchor !== null && (
                        <span className="text-muted-foreground">
                            {' '}
                            · {value.anchor}
                        </span>
                    )}
                </span>
            );
        case 'options':
            return <span>{value.labels.join(', ')}</span>;
        case 'boolean':
            return (
                <span>
                    {t(
                        value.value
                            ? 'occupations.show.yes'
                            : 'occupations.show.no',
                    )}
                </span>
            );
        case 'text':
            return <span>{value.text}</span>;
    }
}
