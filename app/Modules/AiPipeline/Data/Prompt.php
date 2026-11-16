<?php

namespace App\Modules\AiPipeline\Data;

use App\Modules\AiPipeline\Exceptions\InvalidPrompt;

/**
 * One prompt file, `data/prompts/<wave>/<name>.v<N>.md`, read by PromptRegistry. Its front matter holds
 * the model parameters and the output schema; the body has `{{ name }}` placeholders.
 */
final readonly class Prompt
{
    /**
     * @param  list<string>  $variables
     */
    public function __construct(
        public string $key,
        public int $version,
        public string $schemaJson,
        public int $maxOutputTokens,
        public float $temperature,
        public array $variables,
        public string $body,
        public string $file,
    ) {}

    /**
     * Fills in the placeholders. Every declared variable must be given, and nothing else.
     *
     * @param  array<string, string>  $values
     */
    public function render(array $values): string
    {
        $missing = array_diff($this->variables, array_keys($values));
        $extra = array_diff(array_keys($values), $this->variables);

        if ($missing !== [] || $extra !== []) {
            throw InvalidPrompt::because($this->file, sprintf(
                'the values do not match the variables (missing: %s; not declared: %s)',
                implode(', ', $missing) ?: 'none',
                implode(', ', $extra) ?: 'none',
            ));
        }

        $replacements = [];

        foreach ($values as $name => $value) {
            $replacements['{{ '.$name.' }}'] = $value;
        }

        return strtr($this->body, $replacements);
    }
}
