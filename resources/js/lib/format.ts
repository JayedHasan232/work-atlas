import type { Locale } from '@/types/localization';

/**
 * Times are stored in UTC and shown in Bangladesh time. In the `bn` locale, Intl writes Bangla
 * digits (২০২৬); in `en`, Latin digits with day-month-year order.
 */
export const DISPLAY_TIME_ZONE = 'Asia/Dhaka';

const LOCALE_TAGS: Record<Locale, string> = {
    bn: 'bn-BD',
    en: 'en-GB',
};

export function formatNumber(
    value: number,
    locale: Locale,
    options?: Intl.NumberFormatOptions,
): string {
    return new Intl.NumberFormat(LOCALE_TAGS[locale], options).format(value);
}

export function formatDate(
    value: Date | string | number,
    locale: Locale,
    options: Intl.DateTimeFormatOptions = { dateStyle: 'long' },
): string {
    return new Intl.DateTimeFormat(LOCALE_TAGS[locale], {
        ...options,
        timeZone: DISPLAY_TIME_ZONE,
    }).format(new Date(value));
}
