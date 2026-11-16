import { Link } from '@inertiajs/react';
import type { OccupationLink } from '@/types/occupations';

export default function OccupationList({
    occupations,
}: {
    occupations: OccupationLink[];
}) {
    return (
        <ul className="flex flex-col divide-y rounded-md border">
            {occupations.map((occupation) => (
                <li key={occupation.slug}>
                    <Link
                        href={occupation.url}
                        className="block p-3 hover:bg-accent"
                        lang={occupation.nameLocale}
                    >
                        {occupation.name}
                    </Link>
                </li>
            ))}
        </ul>
    );
}
