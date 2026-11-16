import type { Locale } from '@/types/localization';

// The props of the public occupation pages, as Publishing's presenters build them (PLAN M13).

export type FieldStatus =
    | 'ai_draft'
    | 'sourced'
    | 'community_supported'
    | 'verified'
    | 'disputed';

export type NameType = 'primary' | 'alias' | 'local' | 'regional';

export type DisplayValue =
    | { kind: 'unknown' }
    | { kind: 'scale'; point: number; anchor: string | null }
    | { kind: 'options'; labels: string[] }
    | { kind: 'boolean'; value: boolean }
    | { kind: 'text'; text: string };

export type PublicField = {
    key: string;
    label: string;
    help: string | null;
    status: FieldStatus | null;
    /** A sensitive value that is not verified yet: no value and no sources are sent. */
    withheld: boolean;
    value: DisplayValue | null;
    valueLocale: Locale | null;
    sources: number[];
};

export type FieldSection = {
    key: string;
    title: string;
    medical: boolean;
    fields: PublicField[];
};

export type NameList = {
    locale: Locale;
    status: FieldStatus;
    items: { name: string; type: NameType; region: string | null }[];
};

export type PublicSource = {
    number: number;
    url: string;
    title: string | null;
    publisher: string | null;
    license: string | null;
    accessedOn: string;
    archiveUrl: string | null;
};

export type GroupLink = {
    code: string;
    level?: number;
    title: string;
    titleLocale: Locale;
    url: string;
    occupations?: number | null;
};

export type PublicOccupation = {
    slug: string;
    iscoCode: string;
    name: string;
    nameLocale: Locale;
    names: NameList[];
    groups: GroupLink[];
    sections: FieldSection[];
    sources: PublicSource[];
    unverified: boolean;
    changedAt: string | null;
};

export type OccupationLink = {
    slug: string;
    iscoCode: string;
    name: string;
    nameLocale: Locale;
    url: string;
};
