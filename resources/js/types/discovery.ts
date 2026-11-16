import type {
    DisplayValue,
    FieldStatus,
    OccupationLink,
} from '@/types/occupations';

// The props of the guided filter and compare pages (PLAN M20), as Publishing's presenters build them.

export type GuideQuestion = {
    key: string;
    title: string;
    help: string;
    options: { key: string; label: string }[];
};

export type GuideReason = {
    question: string;
    answer: string;
    field: string;
    value: DisplayValue | null;
    status: FieldStatus | null;
    outcome: 'met' | 'partly';
};

export type GuideMatch = {
    occupation: OccupationLink;
    reasons: GuideReason[];
    limits: string[];
};

export type Guide = {
    questions: GuideQuestion[];
    answers: Record<string, string>;
    results: {
        total: number;
        checked: number;
        conditions: number;
        matches: GuideMatch[];
        limits: string[];
    } | null;
    guideUrl: string;
    compareUrl: string;
};

export type CompareCell = {
    value: DisplayValue | null;
    status: FieldStatus;
    withheld: boolean;
} | null;

export type Compare = {
    occupations: (OccupationLink & { removeUrl: string })[];
    rows: {
        key: string;
        label: string;
        help: string | null;
        cells: CompareCell[];
    }[];
    max: number;
    query: string | null;
    hits: (OccupationLink & { matchedName: string; addUrl: string })[];
    searchUrl: string;
    chosen: string[];
};
