import { Head } from '@inertiajs/react';
import { useTranslation } from '@/lib/i18n';
import type { TranslationKey } from '@/types/translation-keys.generated';

type Operation = {
    method: string;
    path: string;
    operationId: string;
    parameters: { name: string; in: string; required: boolean }[];
};

type Docs = {
    operations: Operation[];
    baseUrl: string;
    specUrl: string;
    license: string;
    licenseUrl: string;
    attribution: string;
    perMinute: number;
};

// The public API's docs (PLAN M21): endpoints from the OpenAPI description, and the rules of use.
export default function ApiDocs({ docs }: { docs: Docs }) {
    const { t, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('api.title')} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8">
                <header className="flex flex-col gap-2">
                    <h1 className="text-2xl font-semibold">{t('api.title')}</h1>
                    <p className="text-muted-foreground">{t('api.intro')}</p>
                    <dl className="grid gap-1 text-sm sm:grid-cols-[auto_1fr] sm:gap-x-4">
                        <dt className="font-medium">{t('api.base_url')}</dt>
                        <dd>
                            <code className="break-all">{docs.baseUrl}</code>
                        </dd>
                    </dl>
                    <p>
                        <a
                            href={docs.specUrl}
                            className="text-sm underline underline-offset-4"
                        >
                            {t('api.spec')}
                        </a>
                    </p>
                </header>

                <section
                    aria-labelledby="api-rules"
                    className="flex flex-col gap-2 rounded-lg border p-4"
                >
                    <h2 id="api-rules" className="font-medium">
                        {t('api.rules_title')}
                    </h2>
                    <ul className="list-disc space-y-1 ps-5 text-sm">
                        <li>
                            {t('api.rules.license', {
                                license: docs.license,
                                attribution: docs.attribution,
                            })}{' '}
                            <a
                                href={docs.licenseUrl}
                                rel="license"
                                className="underline underline-offset-4"
                            >
                                {docs.license}
                            </a>
                        </li>
                        <li>{t('api.rules.status')}</li>
                        <li>{t('api.rules.sensitive')}</li>
                        <li>{t('api.rules.advice')}</li>
                        <li>
                            {t('api.rules.limit', {
                                count: formatNumber(docs.perMinute),
                            })}
                        </li>
                    </ul>
                </section>

                <section
                    aria-labelledby="api-endpoints"
                    className="flex flex-col gap-4"
                >
                    <h2 id="api-endpoints" className="text-lg font-medium">
                        {t('api.endpoints')}
                    </h2>
                    {docs.operations.map((operation) => (
                        <article
                            key={operation.operationId}
                            className="flex flex-col gap-2 rounded-lg border p-4"
                            data-test="api-operation"
                        >
                            <h3
                                className="font-mono text-sm break-all"
                                lang="en"
                            >
                                <span className="font-semibold">
                                    {operation.method}
                                </span>{' '}
                                {operation.path}
                            </h3>
                            <p className="text-sm">
                                {t(
                                    `api.operations.${operation.operationId}` as TranslationKey,
                                )}
                            </p>
                            <div className="text-sm">
                                <h4 className="font-medium">
                                    {t('api.parameters')}
                                </h4>
                                {operation.parameters.length === 0 ? (
                                    <p className="text-muted-foreground">
                                        {t('api.no_parameters')}
                                    </p>
                                ) : (
                                    <ul className="flex flex-wrap gap-2">
                                        {operation.parameters.map(
                                            (parameter) => (
                                                <li
                                                    key={parameter.name}
                                                    className="rounded border px-2 py-0.5"
                                                >
                                                    <code lang="en">
                                                        {parameter.name}
                                                    </code>
                                                    {parameter.required &&
                                                        ` (${t('api.required')})`}
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                )}
                            </div>
                        </article>
                    ))}
                </section>
            </div>
        </>
    );
}
