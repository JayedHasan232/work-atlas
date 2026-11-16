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
            [key: string]: unknown;
        };
    }
}
