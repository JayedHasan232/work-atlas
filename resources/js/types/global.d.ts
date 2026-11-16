import type { Auth } from '@/types/auth';
import type { Localization, Translations } from '@/types/localization';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            localization: Localization;
            translations: Translations;
            /** Set on pages kept out of search engines (rule 12). */
            robots?: string | null;
            /** A one-time notice after a form, as a translation key (PLAN M16). */
            notice?: string | null;
            [key: string]: unknown;
        };
    }
}
