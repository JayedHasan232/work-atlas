import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useId } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/lib/i18n';

/**
 * Occupation search (PLAN M15). A plain GET form, so it also works before the page's script has
 * loaded; with the script, Inertia visits the results page without a full reload.
 */
export default function SearchBox({
    defaultValue = '',
    autoFocus = false,
}: {
    defaultValue?: string;
    autoFocus?: boolean;
}) {
    const { t, localizePath } = useTranslation();
    const id = useId();
    const action = localizePath('/search');

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const query = new FormData(event.currentTarget).get('q');
        router.get(action, { q: typeof query === 'string' ? query : '' });
    };

    return (
        <form
            role="search"
            action={action}
            method="get"
            onSubmit={submit}
            className="flex w-full items-center gap-2"
        >
            <label htmlFor={id} className="sr-only">
                {t('common.search.label')}
            </label>
            <Input
                id={id}
                type="search"
                name="q"
                defaultValue={defaultValue}
                placeholder={t('common.search.placeholder')}
                autoFocus={autoFocus}
                maxLength={100}
                enterKeyHint="search"
                className="min-w-0 flex-1"
            />
            <Button type="submit" variant="outline">
                <Search aria-hidden="true" />
                <span className="sr-only sm:not-sr-only">
                    {t('common.search.submit')}
                </span>
            </Button>
        </form>
    );
}
