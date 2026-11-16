<?php

namespace App\Modules\Knowledge\Data;

/**
 * How far a data wave has come for a set of occupations (PLAN M23, D80): a slot is one occupation and
 * one field of the wave (national, no locale); filled slots have a value, supported ones a value at
 * `community_supported` or `verified`.
 */
final readonly class WaveCoverage
{
    public function __construct(
        public int $slots,
        public int $filled,
        public int $supported,
    ) {}

    /** Supported slots as a share of all slots, 0–100 with one decimal; 0 when there are no slots. */
    public function supportedPercent(): float
    {
        return $this->slots === 0 ? 0.0 : round($this->supported / $this->slots * 100, 1);
    }
}
