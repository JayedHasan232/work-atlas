import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import LanguageSwitcher from '@/components/language-switcher';
import LocaleHead from '@/components/locale-head';
import SearchBox from '@/components/search-box';
import { useTranslation } from '@/lib/i18n';
import { dashboard, home, login, register } from '@/routes';

const SOURCE_URL = 'https://github.com/JayedHasan232/work-atlas';
const CONTENT_LICENSE_URL = 'https://creativecommons.org/licenses/by-sa/4.0/';
const CODE_LICENSE_URL = 'https://www.gnu.org/licenses/agpl-3.0.html';

/** Header, language switcher and footer with the license notices, for every public page. */
export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const page = usePage();
    const { auth } = page.props;
    const { t, formatDate, localizePath } = useTranslation();
    const linkClass =
        'inline-block rounded-md px-3 py-1.5 text-sm leading-normal hover:bg-accent';
    const footerLinkClass =
        'underline underline-offset-4 hover:text-foreground';

    return (
        <div className="flex min-h-svh flex-col bg-background text-foreground">
            <LocaleHead />
            <a
                href="#main"
                className="sr-only rounded-md bg-background px-3 py-2 focus:not-sr-only focus:absolute focus:top-2 focus:left-2"
            >
                {t('common.skip_to_content')}
            </a>

            <header className="border-b">
                <div className="mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <Link
                        href={localizePath(home().url)}
                        className="flex items-center gap-2 font-semibold"
                    >
                        <AppLogoIcon
                            aria-hidden="true"
                            className="size-7 fill-current"
                        />
                        <span>{t('common.app_name')}</span>
                    </Link>

                    <div className="flex flex-wrap items-center gap-3">
                        <LanguageSwitcher />
                        <nav aria-label={t('common.nav.account')}>
                            <ul className="flex items-center gap-1">
                                <li>
                                    <Link
                                        href={localizePath('/occupations')}
                                        className={linkClass}
                                    >
                                        {t('common.nav.occupations')}
                                    </Link>
                                </li>
                                {auth.user ? (
                                    <li>
                                        <Link
                                            href={dashboard()}
                                            className={linkClass}
                                        >
                                            {t('common.nav.dashboard')}
                                        </Link>
                                    </li>
                                ) : (
                                    <>
                                        <li>
                                            <Link
                                                href={login()}
                                                className={linkClass}
                                            >
                                                {t('common.nav.log_in')}
                                            </Link>
                                        </li>
                                        <li>
                                            <Link
                                                href={register()}
                                                className={`${linkClass} border`}
                                            >
                                                {t('common.nav.register')}
                                            </Link>
                                        </li>
                                    </>
                                )}
                            </ul>
                        </nav>
                    </div>
                </div>
                {page.component !== 'search/index' && (
                    <div className="mx-auto w-full max-w-5xl px-4 pb-3">
                        <SearchBox />
                    </div>
                )}
            </header>

            <main id="main" className="flex-1">
                {children}
            </main>

            <footer className="border-t text-sm text-muted-foreground">
                <div className="mx-auto flex w-full max-w-5xl flex-col gap-2 px-4 py-6">
                    <p className="max-w-prose">{t('common.footer.about')}</p>
                    <p>
                        {t('common.footer.content_license')}{' '}
                        <a
                            href={CONTENT_LICENSE_URL}
                            rel="license"
                            className={footerLinkClass}
                        >
                            CC BY-SA 4.0
                        </a>
                        {' · '}
                        {t('common.footer.code_license')}{' '}
                        <a
                            href={CODE_LICENSE_URL}
                            rel="license"
                            className={footerLinkClass}
                        >
                            AGPL-3.0
                        </a>
                        {' · '}
                        <a href={SOURCE_URL} className={footerLinkClass}>
                            {t('common.footer.source_code')}
                        </a>
                    </p>
                    <p>
                        {t('common.footer.copyright', {
                            year: formatDate(new Date(), { year: 'numeric' }),
                        })}
                    </p>
                </div>
            </footer>
        </div>
    );
}
