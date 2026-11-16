<?php

namespace App\Modules\Discovery\Data;

/**
 * A person's answers to the guided filter (PLAN M20). They come from the URL and are never stored:
 * the answers belong to the person. An unknown question or option is dropped.
 */
final readonly class GuideAnswers
{
    /**
     * @param  array<string, string>  $answers  question key => option key
     */
    private function __construct(public array $answers) {}

    /**
     * @param  array<array-key, mixed>  $input
     */
    public static function from(array $input): self
    {
        $answers = [];

        foreach (GuideQuestions::QUESTIONS as $question => $definition) {
            $answer = $input[$question] ?? null;

            if (is_string($answer) && in_array($answer, $definition['options'], true)) {
                $answers[$question] = $answer;
            }
        }

        return new self($answers);
    }

    public function get(string $question): ?string
    {
        return $this->answers[$question] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->answers === [];
    }

    /**
     * The answers that set a condition, without "any" and the like.
     *
     * @return array<string, string>
     */
    public function conditions(): array
    {
        return array_filter(
            $this->answers,
            fn (string $answer, string $question): bool => ! in_array($answer, GuideQuestions::QUESTIONS[$question]['open'], true),
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
