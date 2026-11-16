import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import { edit, update } from '@/routes/contributions';
import type { BreadcrumbItem } from '@/types';

// Settings → Contributions (PLAN M16): credit with or without the person's name.
export default function Contributions({ anonymous }: { anonymous: boolean }) {
    const { t } = useTranslation();
    const { data, setData, patch, processing } = useForm({ anonymous });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(update().url, { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('contribute.settings.title')} />

            <h1 className="sr-only">{t('contribute.settings.title')}</h1>

            <form onSubmit={submit} className="space-y-6">
                <Heading
                    variant="small"
                    title={t('contribute.settings.title')}
                    description={t('contribute.settings.description')}
                />
                <div className="flex items-start gap-3">
                    <Checkbox
                        id="anonymous"
                        checked={data.anonymous}
                        onCheckedChange={(checked) =>
                            setData('anonymous', checked === true)
                        }
                        aria-describedby="anonymous-hint"
                        className="mt-0.5"
                    />
                    <div className="grid gap-1">
                        <Label htmlFor="anonymous">
                            {t('contribute.settings.anonymous')}
                        </Label>
                        <p
                            id="anonymous-hint"
                            className="text-sm text-muted-foreground"
                        >
                            {t('contribute.settings.anonymous_hint')}
                        </p>
                    </div>
                </div>
                <Button type="submit" disabled={processing}>
                    {t('contribute.settings.save')}
                </Button>
            </form>
        </>
    );
}

Contributions.layout = {
    breadcrumbs: [
        {
            title: 'contribute.settings.title',
            href: edit(),
        },
    ],
} satisfies { breadcrumbs: BreadcrumbItem[] };
