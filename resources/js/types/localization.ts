export type Locale = 'bn' | 'en';

export type Localization = {
    locale: Locale;
    /** URL prefix of the current locale's public pages: '' for Bangla, '/en' for English. */
    prefix: string;
    /** The page's canonical URL, or null on pages that have one URL for both languages. */
    canonical: string | null;
    locales: {
        code: Locale;
        name: string;
        /** The same page in this locale, or null when the page has one URL for both languages. */
        url: string | null;
    }[];
};

export type Translations = Record<string, unknown>;
