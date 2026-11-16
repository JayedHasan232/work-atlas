<?php

namespace App\Modules\Community\Actions;

use App\Modules\Community\Enums\FlagReason;
use App\Modules\Community\Models\RevisionFlag;
use App\Modules\Knowledge\Data\EditRecord;
use App\Modules\Knowledge\Queries\Edits;
use Illuminate\Support\Facades\Config;

/**
 * Flags a person's edit that may be promotional (PLAN M18, D67), for example a training centre adding
 * itself to occupation pages. Simple, explainable rules, no AI: a declared interest, links (sources or
 * web addresses in the text), an organisation name or phone number, and a link domain or organisation
 * name the same person keeps adding on other occupations. A flag sorts the patrol queue to the top;
 * it never blocks or changes the edit.
 */
final class FlagPromotionalEdit
{
    private const string URL = '~(?:https?://|www\.)[^\s<>"\'()]+~iu';

    private const string PHONE = '~(?<!\d)(?:\+?880[\s-]?|0)1[3-9]\d{2}[\s-]?\d{6}(?!\d)~u';

    private const array BANGLA_DIGITS = ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9'];

    private const string WORD = '[\p{L}\p{M}\p{N}&.\'-]+';

    public function __construct(
        private readonly Edits $edits,
    ) {}

    /**
     * @return list<FlagReason>
     */
    public function handle(int $revisionId): array
    {
        $edit = $this->edits->find($revisionId);

        if ($edit === null || $edit->authorUserId === null) {
            return [];
        }

        $flags = [];
        $domains = $this->domains($edit);
        $organisations = $this->organisations($edit);

        if ($edit->declaredInterest !== null) {
            $flags[FlagReason::Interest->value] = $edit->declaredInterest;
        }

        if ($domains !== []) {
            $flags[FlagReason::Link->value] = implode(', ', $domains);
        }

        if ($organisations !== []) {
            $flags[FlagReason::Organisation->value] = implode(', ', $organisations);
        }

        if ($domains !== [] || $organisations !== []) {
            $flags += $this->repeats($edit, $domains, $organisations);
        }

        if ($flags === []) {
            return [];
        }

        $rows = [];

        foreach ($flags as $reason => $detail) {
            $rows[] = ['revision_id' => $edit->revisionId, 'reason' => $reason, 'detail' => mb_substr($detail, 0, 1000)];
        }

        RevisionFlag::query()->insertOrIgnore($rows);

        return array_map(fn (string $reason): FlagReason => FlagReason::from($reason), array_keys($flags));
    }

    /**
     * A domain or organisation name this person added on enough other occupations recently.
     *
     * @param  list<string>  $domains
     * @param  list<string>  $organisations
     * @return array<string, string>
     */
    private function repeats(EditRecord $edit, array $domains, array $organisations): array
    {
        $since = now()->subDays(Config::integer('atlas.moderation.repeat_days'));
        $after = Config::integer('atlas.moderation.repeat_after');
        /** @var array<string, array<int, true>> $domainSeen */
        $domainSeen = [];
        /** @var array<string, array<int, true>> $organisationSeen */
        $organisationSeen = [];

        foreach ($this->edits->byAuthor((int) $edit->authorUserId, $since, $edit->revisionId) as $other) {
            if ($other->occupationId === $edit->occupationId) {
                continue;
            }

            foreach (array_intersect($domains, $this->domains($other)) as $domain) {
                $domainSeen[$domain][$other->occupationId] = true;
            }

            $theirs = $this->organisations($other);

            foreach ($organisations as $organisation) {
                foreach ($theirs as $their) {
                    $same = $this->sameOrganisation($organisation, $their);

                    if ($same !== null) {
                        $organisationSeen[$same][$other->occupationId] = true;
                    }
                }
            }
        }

        $flags = [];
        $repeatedDomains = array_keys(array_filter($domainSeen, fn (array $seen): bool => count($seen) >= $after));
        $repeatedOrganisations = array_keys(array_filter($organisationSeen, fn (array $seen): bool => count($seen) >= $after));

        if ($repeatedDomains !== []) {
            $flags[FlagReason::RepeatedDomain->value] = implode(', ', $repeatedDomains);
        }

        if ($repeatedOrganisations !== []) {
            $flags[FlagReason::RepeatedOrganisation->value] = implode(', ', $repeatedOrganisations);
        }

        return $flags;
    }

    /**
     * The shared name when two found names point at one organisation: the same phone number, or the
     * same last words with at least one word before the term ("dhaka skill institute" in "learn at
     * dhaka skill institute" and "join dhaka skill institute"). A bare term ("institute") is not enough.
     */
    private function sameOrganisation(string $one, string $two): ?string
    {
        if (preg_match('~^\+?\d+$~', $one) === 1 || preg_match('~^\+?\d+$~', $two) === 1) {
            return ltrim($one, '+') === ltrim($two, '+') ? $one : null;
        }

        $a = array_reverse(explode(' ', $one));
        $b = array_reverse(explode(' ', $two));
        $shared = [];

        foreach ($a as $index => $word) {
            if (($b[$index] ?? null) !== $word) {
                break;
            }

            $shared[] = $word;
        }

        if (count($shared) < 2) {
            return null;
        }

        $name = implode(' ', array_reverse($shared));

        // The shared words must hold more than a multi-word term ("training centre") on its own.
        foreach ($this->terms() as $term) {
            if ($name === mb_strtolower($term)) {
                return null;
            }
        }

        return $name;
    }

    /**
     * The web domains an edit links to, through its sources or in its text, without "www.".
     *
     * @return list<string>
     */
    private function domains(EditRecord $edit): array
    {
        $urls = array_map(fn (array $source): string => $source['url'], $edit->sources);

        foreach ($edit->texts() as $text) {
            if (preg_match_all(self::URL, $text, $matches) > 0) {
                array_push($urls, ...$matches[0]);
            }
        }

        $domains = [];

        foreach ($urls as $url) {
            $host = parse_url(str_contains($url, '://') ? $url : "http://{$url}", PHP_URL_HOST);

            if (is_string($host) && $host !== '') {
                $domains[] = preg_replace('~^www\.~', '', mb_strtolower($host)) ?? $host;
            }
        }

        return array_values(array_unique($domains));
    }

    /**
     * Organisation names (up to three words before a term like "Institute") and phone numbers, in
     * lower case.
     *
     * @return list<string>
     */
    private function organisations(EditRecord $edit): array
    {
        $found = [];

        foreach ($edit->texts() as $text) {
            // Phone numbers are often written in Bangla digits.
            if (preg_match_all(self::PHONE, strtr($text, self::BANGLA_DIGITS), $matches) > 0) {
                foreach ($matches[0] as $phone) {
                    $found[] = preg_replace('~[\s-]~', '', $phone) ?? $phone;
                }
            }

            foreach ($this->terms() as $term) {
                $pattern = '~(?:'.self::WORD.'\s+){0,3}(?<![\p{L}\p{M}\p{N}])'.preg_quote($term, '~').'(?![\p{L}\p{M}\p{N}])~iu';

                if (preg_match_all($pattern, $text, $matches) > 0) {
                    foreach ($matches[0] as $name) {
                        $found[] = mb_strtolower(trim(preg_replace('~\s+~u', ' ', $name) ?? $name));
                    }
                }
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * @return list<string>
     */
    private function terms(): array
    {
        /** @var list<string> $terms */
        $terms = Config::array('atlas.moderation.organisation_terms');

        return $terms;
    }
}
