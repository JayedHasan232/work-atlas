import { Link, usePage } from '@inertiajs/react';
import { dashboard, login, register } from '@/routes';

// Placeholder home page until the public occupation pages arrive (M13).
// Its strings move to translation keys with the rest of the UI in M3.
export default function Welcome() {
    const { auth } = usePage().props;
    const linkClass =
        'inline-block rounded-sm border px-5 py-1.5 text-sm leading-normal hover:bg-accent';

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <header className="mx-auto flex w-full max-w-4xl justify-end p-6">
                <nav aria-label="Account" className="flex gap-4">
                    {auth.user ? (
                        <Link href={dashboard()} className={linkClass}>
                            Dashboard
                        </Link>
                    ) : (
                        <>
                            <Link
                                href={login()}
                                className={`${linkClass} border-transparent`}
                            >
                                Log in
                            </Link>
                            <Link href={register()} className={linkClass}>
                                Register
                            </Link>
                        </>
                    )}
                </nav>
            </header>
            <main className="mx-auto flex w-full max-w-4xl flex-1 flex-col justify-center gap-3 p-6">
                <h1 className="text-3xl font-semibold">Work Atlas</h1>
                <p lang="bn" className="text-xl">
                    কাজ
                </p>
                <p className="max-w-prose text-muted-foreground">
                    An open, community-verified database of every occupation in
                    Bangladesh. Work in progress.
                </p>
            </main>
        </div>
    );
}
