<?php

namespace App\Modules\Publishing\Enums;

/**
 * The project's policy pages (PLAN M23), by their address. The text lives in lang/{bn,en}/policies.php
 * under {@see key()}; every text is a draft until the maintainer approves it.
 */
enum Policy: string
{
    case CodeOfConduct = 'code-of-conduct';
    case Editorial = 'editorial-policy';
    case Privacy = 'privacy';
    case NotAdvice = 'not-medical-advice';
    case Neutrality = 'neutrality-and-funding';

    /** The translation group key in lang/{bn,en}/policies.php. */
    public function key(): string
    {
        return match ($this) {
            self::CodeOfConduct => 'code_of_conduct',
            self::Editorial => 'editorial',
            self::Privacy => 'privacy',
            self::NotAdvice => 'not_advice',
            self::Neutrality => 'neutrality',
        };
    }

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_map(fn (self $policy): string => $policy->value, self::cases());
    }
}
