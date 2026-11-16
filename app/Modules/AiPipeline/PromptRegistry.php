<?php

namespace App\Modules\AiPipeline;

use App\Modules\AiPipeline\Data\Prompt;
use App\Modules\AiPipeline\Exceptions\InvalidPrompt;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads prompt files: `<prompts>/<wave>/<name>.v<N>.md` (key `<wave>/<name>`, version N). The file
 * starts with YAML front matter between `---` lines:
 *
 *     version: 1                      # the N in the file name
 *     schema: wave1/fields.v1.json    # the output schema, under data/schemas
 *     max_output_tokens: 4000
 *     temperature: 0
 *     variables: [isco_code, names]   # the {{ placeholders }} the body uses, all of them
 *
 * A prompt file never changes once committed (scripts/ci/check-immutable); a change is a new version.
 */
final class PromptRegistry
{
    private const array KEYS = ['version', 'schema', 'max_output_tokens', 'temperature', 'variables'];

    /** @var array<string, Prompt> */
    private array $loaded = [];

    public function __construct(
        private readonly ?string $promptsPath = null,
        private readonly ?string $schemasPath = null,
    ) {}

    public function get(string $key, int $version): Prompt
    {
        return $this->loaded["{$key}@{$version}"] ??= $this->read($key, $version);
    }

    private function read(string $key, int $version): Prompt
    {
        if (preg_match('/^[a-z0-9_]+\/[a-z0-9_]+$/', $key) !== 1 || $version < 1) {
            throw InvalidPrompt::because("{$key} v{$version}", 'a key is "<wave>/<name>" in lower case, and a version is 1 or more');
        }

        $file = $this->promptsPath()."/{$key}.v{$version}.md";
        $text = is_file($file) ? (string) file_get_contents($file) : throw InvalidPrompt::because($file, 'no such prompt file');

        if (preg_match('/\A---\n(.*?)\n---\n(.*)\z/s', str_replace("\r\n", "\n", $text), $parts) !== 1) {
            throw InvalidPrompt::because($file, 'the file must start with front matter between --- lines');
        }

        try {
            $meta = Yaml::parse($parts[1]);
        } catch (ParseException $exception) {
            throw InvalidPrompt::because($file, 'the front matter is not valid YAML: '.$exception->getMessage());
        }

        if (! is_array($meta) || array_keys($meta) !== array_intersect(array_keys($meta), self::KEYS) || count($meta) !== count(self::KEYS)) {
            throw InvalidPrompt::because($file, 'the front matter needs exactly: '.implode(', ', self::KEYS));
        }

        if ($meta['version'] !== $version) {
            throw InvalidPrompt::because($file, "the front matter says version {$this->show($meta['version'])}, the file name says {$version}");
        }

        $body = trim($parts[2]);

        return new Prompt(
            key: $key,
            version: $version,
            schemaJson: $this->schema($file, $meta['schema']),
            maxOutputTokens: $this->integer($file, 'max_output_tokens', $meta['max_output_tokens'], 1, 200_000),
            temperature: $this->temperature($file, $meta['temperature']),
            variables: $this->variables($file, $meta['variables'], $body),
            body: $body,
            file: $file,
        );
    }

    private function schema(string $file, mixed $schema): string
    {
        if (! is_string($schema) || preg_match('/^[a-z0-9_]+\/[a-z0-9_]+\.v[1-9][0-9]*\.json$/', $schema) !== 1) {
            throw InvalidPrompt::because($file, 'schema must be a path like wave1/fields.v1.json');
        }

        $path = $this->schemasPath()."/{$schema}";
        $json = is_file($path) ? (string) file_get_contents($path) : throw InvalidPrompt::because($file, "the schema {$schema} does not exist");

        if (! is_object(json_decode($json))) {
            throw InvalidPrompt::because($file, "the schema {$schema} is not a JSON object");
        }

        return $json;
    }

    private function integer(string $file, string $name, mixed $value, int $min, int $max): int
    {
        if (! is_int($value) || $value < $min || $value > $max) {
            throw InvalidPrompt::because($file, "{$name} must be a whole number from {$min} to {$max}");
        }

        return $value;
    }

    private function temperature(string $file, mixed $value): float
    {
        if (! is_int($value) && ! is_float($value) || $value < 0 || $value > 2) {
            throw InvalidPrompt::because($file, 'temperature must be a number from 0 to 2');
        }

        return (float) $value;
    }

    /**
     * @return list<string>
     */
    private function variables(string $file, mixed $variables, string $body): array
    {
        if (! is_array($variables) || ! array_is_list($variables)) {
            throw InvalidPrompt::because($file, 'variables must be a list');
        }

        foreach ($variables as $variable) {
            if (! is_string($variable) || preg_match('/^[a-z][a-z0-9_]*$/', $variable) !== 1) {
                throw InvalidPrompt::because($file, 'each variable is a lower-case name');
            }
        }

        if (count(array_unique($variables)) !== count($variables)) {
            throw InvalidPrompt::because($file, 'a variable is listed twice');
        }

        preg_match_all('/\{\{\s*([^}]*?)\s*\}\}/', $body, $matches);
        $used = array_values(array_unique($matches[1]));
        $declared = $variables;
        sort($used);
        sort($declared);

        if ($used !== $declared) {
            throw InvalidPrompt::because($file, sprintf(
                'the body uses {{ %s }} but the front matter lists %s',
                implode(' }}, {{ ', $used) ?: '',
                implode(', ', $variables) ?: 'none',
            ));
        }

        if (preg_match('/\{\{(?! [a-z][a-z0-9_]* \}\})/', $body) === 1) {
            throw InvalidPrompt::because($file, 'write placeholders as {{ name }}, with one space inside the braces');
        }

        return $variables;
    }

    private function show(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : get_debug_type($value);
    }

    private function promptsPath(): string
    {
        return rtrim($this->promptsPath ?? (string) config('atlas.ai.prompts_path'), '/');
    }

    private function schemasPath(): string
    {
        return rtrim($this->schemasPath ?? (string) config('atlas.ai.schemas_path'), '/');
    }
}
