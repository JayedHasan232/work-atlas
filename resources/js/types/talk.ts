import type { Flag, Person } from '@/types/moderation';
import type { Locale } from '@/types/localization';
import type { OccupationLink } from '@/types/occupations';

// The props of the talk pages (PLAN M19), as Publishing's TalkPresenter builds them.

export type TalkAuthor = { name: string; url: string };

export type TalkField = { key: string; label: string };

export type TalkViewer = {
    signedIn: boolean;
    canPost: boolean;
    canModerate: boolean;
};

export type TalkThreadSummary = {
    id: number;
    title: string;
    titleLocale: Locale;
    field: TalkField | null;
    author: TalkAuthor | null;
    postsCount: number;
    lastPostedAt: string | null;
    hidden: boolean;
    url: string;
};

export type TalkIndex = {
    occupation: OccupationLink;
    threads: TalkThreadSummary[];
    fields: TalkField[];
    selectedField: string | null;
    viewer: TalkViewer;
    storeUrl: string;
};

export type TalkPost = {
    id: number;
    author: TalkAuthor | null;
    body: string | null;
    locale: Locale;
    hidden: boolean;
    createdAt: string | null;
    flags: Flag[];
    hideUrl: string | null;
    showUrl: string | null;
};

export type TalkThread = {
    occupation: OccupationLink;
    talkUrl: string;
    thread: {
        id: number;
        title: string | null;
        titleLocale: Locale;
        field: TalkField | null;
        author: TalkAuthor | null;
        hidden: boolean;
        createdAt: string | null;
        hideUrl: string | null;
        showUrl: string | null;
    };
    posts: TalkPost[];
    viewer: TalkViewer;
    replyUrl: string | null;
};

export type TalkQueuePost = {
    postId: number;
    occupation: OccupationLink;
    threadTitle: string;
    author: Person | null;
    body: string;
    locale: Locale;
    flags: Flag[];
    createdAt: string | null;
    threadUrl: string;
    hideUrl: string;
};
