import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FieldValue from '@/components/occupations/field-value';
import StatusBadge from '@/components/occupations/status-badge';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import type {
    DisplayValue,
    FieldStatus,
    OccupationLink,
    PublicField,
} from '@/types/occupations';

type FieldType =
    | 'scale_1_5'
    | 'enum'
    | 'multi_enum'
    | 'boolean'
    | 'band'
    | 'text';

type Props = {
    form: {
        occupation: OccupationLink;
        field: {
            key: string;
            label: string;
            help: string | null;
            type: FieldType;
            options: { value: string; label: string }[];
            anchors: { point: number; label: string | null }[];
            requiresSource: boolean;
            sensitive: boolean;
        };
        current: {
            status: FieldStatus;
            withheld: boolean;
            value: DisplayValue | null;
        } | null;
        submitUrl: string;
        pageUrl: string;
    };
};

type Value = number | string | string[] | boolean | null;

type SourceRow = {
    url: string;
    accessed_on: string;
    title: string;
    publisher: string;
};

const controlClass =
    'w-full rounded-md border border-input bg-background px-3 py-2 text-base shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none md:text-sm';

const MAX_SOURCES = 5;

function today(): string {
    return new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Dhaka' });
}

// Suggest a change to one field of an occupation (PLAN M16): an input for the field's type, sources, a
// comment and a conflict-of-interest declaration, with the current and the suggested value side by side.
export default function SuggestPage({ form }: Props) {
    const { t } = useTranslation();
    const field = form.field;
    const { data, setData, post, processing, errors } = useForm<{
        value: Value;
        sources: SourceRow[];
        comment: string;
        interest: '' | 'none' | 'declared';
        interest_note: string;
    }>({
        value: field.type === 'multi_enum' ? [] : null,
        sources: field.requiresSource
            ? [{ url: '', accessed_on: today(), title: '', publisher: '' }]
            : [],
        comment: '',
        interest: '',
        interest_note: '',
    });
    const fieldErrors = errors as Record<string, string | undefined>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(form.submitUrl, { preserveScroll: true });
    };

    const label = (option: string) =>
        field.options.find((item) => item.value === option)?.label ?? option;

    const suggested = (): DisplayValue | null => {
        const value = data.value;

        if (
            value === null ||
            value === '' ||
            (Array.isArray(value) && value.length === 0)
        ) {
            return null;
        }

        switch (field.type) {
            case 'scale_1_5':
                return {
                    kind: 'scale',
                    point: Number(value),
                    anchor:
                        field.anchors.find((a) => a.point === Number(value))
                            ?.label ?? null,
                };
            case 'boolean':
                return { kind: 'boolean', value: value === true };
            case 'text':
                return { kind: 'text', text: String(value) };
            default:
                return {
                    kind: 'options',
                    labels: (Array.isArray(value)
                        ? value
                        : [String(value)]
                    ).map(label),
                };
        }
    };

    const asField = (
        value: DisplayValue | null,
        withheld = false,
    ): PublicField => ({
        key: field.key,
        label: field.label,
        help: null,
        status: null,
        withheld,
        value,
        valueLocale: null,
        sources: [],
        suggestUrl: '',
        reportUrl: '',
        discussUrl: '',
    });

    const updateSource = (index: number, key: keyof SourceRow, value: string) =>
        setData(
            'sources',
            data.sources.map((row, i) =>
                i === index ? { ...row, [key]: value } : row,
            ),
        );

    const preview = suggested();

    return (
        <>
            <Head title={`${t('contribute.suggest.title')}: ${field.label}`} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 px-4 py-8">
                <header className="flex flex-col gap-2">
                    <h1 className="text-3xl font-semibold">
                        {t('contribute.suggest.title')}
                    </h1>
                    <p className="text-lg">
                        <span lang={form.occupation.nameLocale}>
                            {form.occupation.name}
                        </span>
                        {' · '}
                        <span className="font-medium">{field.label}</span>
                    </p>
                    {field.help !== null && (
                        <p className="text-sm text-muted-foreground">
                            {field.help}
                        </p>
                    )}
                    <p className="text-sm">
                        {t('contribute.suggest.review_note')}
                    </p>
                </header>

                <form onSubmit={submit} className="flex flex-col gap-6">
                    <fieldset className="flex flex-col gap-2">
                        <legend className="mb-2 font-medium">
                            {t('contribute.suggest.value')}
                        </legend>
                        {field.type === 'scale_1_5' &&
                            field.anchors.map((anchor) => (
                                <label
                                    key={anchor.point}
                                    className="flex items-start gap-2"
                                >
                                    <input
                                        type="radio"
                                        name="value"
                                        value={anchor.point}
                                        className="mt-1"
                                        checked={data.value === anchor.point}
                                        onChange={() =>
                                            setData('value', anchor.point)
                                        }
                                    />
                                    <span>
                                        <span className="font-medium">
                                            {anchor.point}
                                        </span>
                                        {anchor.label !== null &&
                                            ` · ${anchor.label}`}
                                    </span>
                                </label>
                            ))}
                        {(field.type === 'enum' || field.type === 'band') &&
                            field.options.map((option) => (
                                <label
                                    key={option.value}
                                    className="flex items-start gap-2"
                                >
                                    <input
                                        type="radio"
                                        name="value"
                                        value={option.value}
                                        className="mt-1"
                                        checked={data.value === option.value}
                                        onChange={() =>
                                            setData('value', option.value)
                                        }
                                    />
                                    <span>{option.label}</span>
                                </label>
                            ))}
                        {field.type === 'multi_enum' &&
                            field.options.map((option) => {
                                const chosen = Array.isArray(data.value)
                                    ? data.value
                                    : [];

                                return (
                                    <label
                                        key={option.value}
                                        className="flex items-start gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            name="value[]"
                                            value={option.value}
                                            className="mt-1"
                                            checked={chosen.includes(
                                                option.value,
                                            )}
                                            onChange={(event) =>
                                                setData(
                                                    'value',
                                                    event.target.checked
                                                        ? [
                                                              ...chosen,
                                                              option.value,
                                                          ]
                                                        : chosen.filter(
                                                              (item) =>
                                                                  item !==
                                                                  option.value,
                                                          ),
                                                )
                                            }
                                        />
                                        <span>{option.label}</span>
                                    </label>
                                );
                            })}
                        {field.type === 'boolean' &&
                            [true, false].map((choice) => (
                                <label
                                    key={String(choice)}
                                    className="flex items-center gap-2"
                                >
                                    <input
                                        type="radio"
                                        name="value"
                                        value={String(choice)}
                                        checked={data.value === choice}
                                        onChange={() =>
                                            setData('value', choice)
                                        }
                                    />
                                    <span>
                                        {t(
                                            choice
                                                ? 'contribute.suggest.yes'
                                                : 'contribute.suggest.no',
                                        )}
                                    </span>
                                </label>
                            ))}
                        {field.type === 'text' && (
                            <textarea
                                aria-label={t('contribute.suggest.value')}
                                rows={5}
                                maxLength={5000}
                                value={
                                    typeof data.value === 'string'
                                        ? data.value
                                        : ''
                                }
                                onChange={(event) =>
                                    setData('value', event.target.value)
                                }
                                className={controlClass}
                            />
                        )}
                        <InputError message={errors.value} />
                    </fieldset>

                    <section
                        aria-labelledby="preview"
                        className="flex flex-col gap-2 rounded-md border p-3"
                        data-test="diff-preview"
                    >
                        <h2 id="preview" className="font-medium">
                            {t('contribute.suggest.preview')}
                        </h2>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="flex flex-col gap-1">
                                <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                    {t('contribute.suggest.current')}
                                    {form.current !== null && (
                                        <StatusBadge
                                            status={form.current.status}
                                        />
                                    )}
                                </p>
                                {form.current === null ? (
                                    <p className="text-muted-foreground">
                                        {t('contribute.suggest.nothing_yet')}
                                    </p>
                                ) : (
                                    <FieldValue
                                        field={asField(
                                            form.current.value,
                                            form.current.withheld,
                                        )}
                                    />
                                )}
                            </div>
                            <div className="flex flex-col gap-1">
                                <p className="text-sm text-muted-foreground">
                                    {t('contribute.suggest.yours')}
                                </p>
                                {preview === null ? (
                                    <p className="text-muted-foreground">
                                        {t('contribute.suggest.not_chosen')}
                                    </p>
                                ) : (
                                    <FieldValue field={asField(preview)} />
                                )}
                            </div>
                        </div>
                    </section>

                    <fieldset className="flex flex-col gap-3">
                        <legend className="mb-1 font-medium">
                            {t('contribute.suggest.sources')}
                        </legend>
                        <p className="text-sm text-muted-foreground">
                            {field.requiresSource
                                ? t('contribute.suggest.sources_required')
                                : t('contribute.suggest.sources_hint')}
                        </p>
                        {data.sources.map((source, index) => (
                            <div
                                key={index}
                                className="flex flex-col gap-2 rounded-md border p-3"
                            >
                                {(
                                    [
                                        ['url', 'source_url', 'url'],
                                        [
                                            'accessed_on',
                                            'source_accessed',
                                            'date',
                                        ],
                                        ['title', 'source_title', 'text'],
                                        [
                                            'publisher',
                                            'source_publisher',
                                            'text',
                                        ],
                                    ] as const
                                ).map(([key, text, type]) => (
                                    <div key={key} className="grid gap-1">
                                        <Label
                                            htmlFor={`source-${index}-${key}`}
                                        >
                                            {t(`contribute.suggest.${text}`)}
                                        </Label>
                                        <input
                                            id={`source-${index}-${key}`}
                                            type={type}
                                            required={
                                                key === 'url' ||
                                                key === 'accessed_on'
                                            }
                                            max={
                                                type === 'date'
                                                    ? today()
                                                    : undefined
                                            }
                                            value={source[key]}
                                            onChange={(event) =>
                                                updateSource(
                                                    index,
                                                    key,
                                                    event.target.value,
                                                )
                                            }
                                            className={controlClass}
                                        />
                                        <InputError
                                            message={
                                                fieldErrors[
                                                    `sources.${index}.${key}`
                                                ]
                                            }
                                        />
                                    </div>
                                ))}
                                <button
                                    type="button"
                                    className="self-start text-sm underline underline-offset-4"
                                    onClick={() =>
                                        setData(
                                            'sources',
                                            data.sources.filter(
                                                (_, i) => i !== index,
                                            ),
                                        )
                                    }
                                >
                                    {t('contribute.suggest.remove_source')}
                                </button>
                            </div>
                        ))}
                        {data.sources.length < MAX_SOURCES && (
                            <button
                                type="button"
                                className="self-start rounded-md border px-3 py-1.5 text-sm hover:bg-muted"
                                onClick={() =>
                                    setData('sources', [
                                        ...data.sources,
                                        {
                                            url: '',
                                            accessed_on: today(),
                                            title: '',
                                            publisher: '',
                                        },
                                    ])
                                }
                            >
                                {t('contribute.suggest.add_source')}
                            </button>
                        )}
                        <InputError message={errors.sources} />
                    </fieldset>

                    <div className="grid gap-2">
                        <Label htmlFor="comment">
                            {t('contribute.suggest.comment')}
                        </Label>
                        <textarea
                            id="comment"
                            rows={3}
                            maxLength={2000}
                            value={data.comment}
                            onChange={(event) =>
                                setData('comment', event.target.value)
                            }
                            aria-describedby="comment-hint"
                            className={controlClass}
                        />
                        <p
                            id="comment-hint"
                            className="text-sm text-muted-foreground"
                        >
                            {t('contribute.suggest.comment_hint')}
                        </p>
                        <InputError message={errors.comment} />
                    </div>

                    <fieldset className="flex flex-col gap-2">
                        <legend className="mb-1 font-medium">
                            {t('contribute.suggest.interest')}
                        </legend>
                        <p className="text-sm text-muted-foreground">
                            {t('contribute.suggest.interest_hint')}
                        </p>
                        <label className="flex items-center gap-2">
                            <input
                                type="radio"
                                name="interest"
                                value="none"
                                required
                                checked={data.interest === 'none'}
                                onChange={() => setData('interest', 'none')}
                            />
                            <span>{t('contribute.suggest.interest_none')}</span>
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="radio"
                                name="interest"
                                value="declared"
                                checked={data.interest === 'declared'}
                                onChange={() => setData('interest', 'declared')}
                            />
                            <span>
                                {t('contribute.suggest.interest_declared')}
                            </span>
                        </label>
                        {data.interest === 'declared' && (
                            <div className="grid gap-1">
                                <Label htmlFor="interest_note">
                                    {t('contribute.suggest.interest_note')}
                                </Label>
                                <input
                                    id="interest_note"
                                    type="text"
                                    required
                                    maxLength={500}
                                    value={data.interest_note}
                                    onChange={(event) =>
                                        setData(
                                            'interest_note',
                                            event.target.value,
                                        )
                                    }
                                    className={controlClass}
                                />
                            </div>
                        )}
                        <InputError
                            message={errors.interest ?? errors.interest_note}
                        />
                    </fieldset>

                    <p className="text-sm text-muted-foreground">
                        {t('contribute.suggest.license')}
                    </p>

                    <div className="flex flex-wrap items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            {t('contribute.suggest.submit')}
                        </Button>
                        <Link
                            href={form.pageUrl}
                            className="text-sm underline underline-offset-4"
                        >
                            {t('contribute.suggest.back')}
                        </Link>
                    </div>
                </form>
            </div>
        </>
    );
}
