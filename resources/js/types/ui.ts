import type { ReactNode } from 'react';
import type { BreadcrumbItem } from '@/types/navigation';
import type { TranslationKey } from '@/types/translation-keys.generated';

export type AppLayoutProps = {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
};

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

/** `title` and `description` are translation keys: pages set them in static layout props. */
export type AuthLayoutProps = {
    children?: ReactNode;
    title?: TranslationKey;
    description?: TranslationKey;
};
