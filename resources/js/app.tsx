import { createInertiaApp, router } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import PublicLayout from '@/layouts/public-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { Locale } from '@/types/localization';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome' ||
                name === 'contribution-terms' ||
                name.startsWith('occupations/') ||
                name.startsWith('search/') ||
                name.startsWith('contributors/') ||
                name.startsWith('tasks/'):
                return PublicLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();

// The server sets <html lang> on the first load; keep it right when the language changes without a
// full page load. `navigate` covers links and history; `success` also covers a redirect back to the
// same URL (the switcher on auth and settings pages), which Inertia treats as a replace and for
// which it fires no `navigate`.
if (typeof window !== 'undefined') {
    const setDocumentLanguage = (locale: Locale) => {
        document.documentElement.lang = locale;
    };

    router.on('navigate', (event) =>
        setDocumentLanguage(event.detail.page.props.localization.locale),
    );
    router.on('success', (event) =>
        setDocumentLanguage(event.detail.page.props.localization.locale),
    );
}
