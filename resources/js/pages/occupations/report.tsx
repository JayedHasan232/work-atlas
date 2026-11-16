import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import type { OccupationLink } from '@/types/occupations';

type Props = {
    form: {
        occupation: OccupationLink;
        fields: { key: string; label: string }[];
        field: string | null;
        submitUrl: string;
    };
};

const controlClass =
    'w-full rounded-md border border-input bg-background px-3 py-2 text-base shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none md:text-sm';

// Report a mistake on an occupation page, without an account (PLAN M16). The `website` field is a
// honeypot: hidden from people, filled only by bots.
export default function ReportPage({ form }: Props) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        field: form.field ?? '',
        message: '',
        website: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(form.submitUrl, { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('contribute.report.title')} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 px-4 py-8">
                <header className="flex flex-col gap-2">
                    <h1 className="text-3xl font-semibold">
                        {t('contribute.report.title')}
                    </h1>
                    <p className="text-lg" lang={form.occupation.nameLocale}>
                        {form.occupation.name}
                    </p>
                    <p>{t('contribute.report.intro')}</p>
                </header>

                <form onSubmit={submit} className="flex flex-col gap-5">
                    <div className="grid gap-2">
                        <Label htmlFor="field">
                            {t('contribute.report.field')}
                        </Label>
                        <select
                            id="field"
                            value={data.field}
                            onChange={(event) =>
                                setData('field', event.target.value)
                            }
                            className={controlClass}
                        >
                            <option value="">
                                {t('contribute.report.whole_page')}
                            </option>
                            {form.fields.map((field) => (
                                <option key={field.key} value={field.key}>
                                    {field.label}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.field} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="message">
                            {t('contribute.report.message')}
                        </Label>
                        <textarea
                            id="message"
                            required
                            rows={5}
                            maxLength={2000}
                            value={data.message}
                            onChange={(event) =>
                                setData('message', event.target.value)
                            }
                            aria-describedby="message-hint"
                            className={controlClass}
                        />
                        <p
                            id="message-hint"
                            className="text-sm text-muted-foreground"
                        >
                            {t('contribute.report.message_hint')}
                        </p>
                        <InputError message={errors.message} />
                    </div>

                    <div className="hidden" aria-hidden="true">
                        <label htmlFor="website">
                            {t('contribute.report.honeypot')}
                        </label>
                        <input
                            id="website"
                            type="text"
                            tabIndex={-1}
                            autoComplete="off"
                            value={data.website}
                            onChange={(event) =>
                                setData('website', event.target.value)
                            }
                        />
                    </div>

                    <div className="flex flex-wrap items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            {t('contribute.report.submit')}
                        </Button>
                        <Link
                            href={form.occupation.url}
                            className="text-sm underline underline-offset-4"
                        >
                            {t('contribute.report.back')}
                        </Link>
                    </div>
                </form>
            </div>
        </>
    );
}
