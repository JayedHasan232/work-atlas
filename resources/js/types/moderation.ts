import type {
    DisplayValue,
    FieldStatus,
    OccupationLink,
} from '@/types/occupations';

// The props of the moderation pages (PLAN M18), as Community's ModerationPresenter builds them.

export type ReviewReason =
    | 'models_disagree'
    | 'community_dissent'
    | 'pending_proposal';

export type EditKind =
    | 'applied'
    | 'proposal'
    | 'accepted'
    | 'rejected'
    | 'revert';

export type FlagReason =
    | 'interest'
    | 'link'
    | 'repeated_domain'
    | 'organisation'
    | 'repeated_organisation';

export type Flag = { reason: FlagReason; detail: string | null };

export type Person = {
    id: number;
    name: string;
    role: string;
    newAccount: boolean;
    blocked: boolean;
};

export type DiffLine = { op: ' ' | '-' | '+'; line: string };

export type ModerationField = {
    key: string;
    label: string;
    help: string | null;
    sensitive: boolean;
};

export type Edit = {
    revisionId: number;
    value: DisplayValue;
    diff: DiffLine[];
    author: Person | null;
    kind: EditKind;
    comment: string | null;
    declaredInterest: string | null;
    sources: { url: string; title: string | null }[];
    createdAt: string | null;
};

export type ReviewQueueItem = {
    fieldValueId: number;
    occupation: OccupationLink | null;
    field: string;
    locale: string | null;
    status: FieldStatus;
    reason: ReviewReason | null;
    proposals: number;
    url: string;
};

export type ReviewSlot = {
    fieldValueId: number;
    occupation: OccupationLink | null;
    field: ModerationField;
    locale: string | null;
    status: FieldStatus;
    reason: ReviewReason | null;
    crossCheck: 'agreed' | 'disagreed' | null;
    current: DisplayValue;
    proposals: (Edit & { reviewUrl: string })[];
    statuses: FieldStatus[];
    statusUrl: string;
    historyUrl: string | null;
};

export type PatrolQueueEntry = {
    revisionId: number;
    occupation: OccupationLink | null;
    field: string;
    author: Person | null;
    kind: EditKind;
    flags: Flag[];
    createdAt: string | null;
    url: string;
};

export type PatrolEdit = Edit & {
    occupation: OccupationLink | null;
    field: ModerationField;
    locale: string | null;
    old: DisplayValue;
    status: FieldStatus;
    current: boolean;
    canRevert: boolean;
    patrolled: boolean;
    flags: Flag[];
    revertUrl: string;
    patrolUrl: string;
    slotUrl: string;
};
