<?php

use App\Modules\AiPipeline\Exceptions\InvalidPrompt;
use App\Modules\AiPipeline\PromptRegistry;

/**
 * A registry over a temporary prompts folder with one file, test/p.v1.md, and the fixture schemas.
 */
function registryWith(string $contents, string $file = 'test/p.v1.md'): PromptRegistry
{
    $root = sys_get_temp_dir().'/work-atlas-prompts-'.bin2hex(random_bytes(8));
    mkdir(dirname("{$root}/{$file}"), recursive: true);
    file_put_contents("{$root}/{$file}", $contents);
    register_shutdown_function(fn () => exec('rm -rf '.escapeshellarg($root)));

    return new PromptRegistry($root, base_path('tests/Fixtures/schemas'));
}

/**
 * @param  array<string, string>  $changes
 */
function promptFile(array $changes = []): string
{
    return strtr(<<<'MD'
        ---
        version: 1
        schema: test/sample.v1.json
        max_output_tokens: 100
        temperature: 0.2
        variables: [occupation]
        ---
        Tell me about {{ occupation }}.

        MD, $changes);
}

it('reads a prompt file with its schema and settings', function () {
    $prompt = (new PromptRegistry(base_path('tests/Fixtures/prompts'), base_path('tests/Fixtures/schemas')))->get('test/sample', 1);

    expect($prompt->key)->toBe('test/sample')
        ->and($prompt->version)->toBe(1)
        ->and($prompt->maxOutputTokens)->toBe(200)
        ->and($prompt->temperature)->toBe(0.0)
        ->and($prompt->variables)->toBe(['occupation', 'isco_code'])
        ->and(json_decode($prompt->schemaJson, true)['type'])->toBe('object')
        ->and($prompt->render(['occupation' => 'Electrician', 'isco_code' => '7411']))->toContain('Electrician, ISCO-08 unit group 7411');
});

it('refuses rendering with missing or undeclared values', function (array $values) {
    $prompt = registryWith(promptFile())->get('test/p', 1);

    expect(fn () => $prompt->render($values))->toThrow(InvalidPrompt::class, 'the values do not match the variables');
})->with([
    'missing' => [[]],
    'undeclared' => [['occupation' => 'x', 'region' => 'y']],
]);

it('does not replace a placeholder inside a value', function () {
    $prompt = registryWith(promptFile(['variables: [occupation]' => 'variables: [occupation, region]', '{{ occupation }}.' => '{{ occupation }} in {{ region }}.']))->get('test/p', 1);

    expect($prompt->render(['occupation' => '{{ region }}', 'region' => 'Sylhet']))->toContain('Tell me about {{ region }} in Sylhet.');
});

it('rejects an invalid prompt file', function (array $changes, string $error, string $key = 'test/p', int $version = 1) {
    expect(fn () => registryWith(promptFile($changes))->get($key, $version))->toThrow(InvalidPrompt::class, $error);
})->with([
    'a bad key' => [[], 'a key is "<wave>/<name>"', 'Test/P'],
    'a missing file' => [[], 'no such prompt file', 'test/p', 2],
    'no front matter' => [["---\nversion: 1" => 'version: 1'], 'front matter between --- lines'],
    'broken YAML' => [['variables: [occupation]' => 'variables: [occupation'], 'not valid YAML'],
    'an unknown key' => [['temperature: 0.2' => "temperature: 0.2\nseed: 4"], 'needs exactly'],
    'a missing key' => [['temperature: 0.2' => ''], 'needs exactly'],
    'a version that differs from the file name' => [['version: 1' => 'version: 2'], 'the file name says 1'],
    'a schema outside the schemas folder' => [['test/sample.v1.json' => '../sample.v1.json'], 'schema must be a path'],
    'a missing schema' => [['sample.v1.json' => 'sample.v9.json'], 'does not exist'],
    'too many output tokens' => [['max_output_tokens: 100' => 'max_output_tokens: 500000'], 'max_output_tokens must be a whole number'],
    'a temperature above 2' => [['temperature: 0.2' => 'temperature: 3'], 'temperature must be a number from 0 to 2'],
    'variables not a list' => [['variables: [occupation]' => 'variables: occupation'], 'variables must be a list'],
    'an upper-case variable' => [['variables: [occupation]' => 'variables: [Occupation]', '{{ occupation }}' => '{{ Occupation }}'], 'each variable is a lower-case name'],
    'a variable listed twice' => [['variables: [occupation]' => 'variables: [occupation, occupation]'], 'listed twice'],
    'an unused variable' => [['variables: [occupation]' => 'variables: [occupation, region]'], 'the front matter lists occupation, region'],
    'an undeclared placeholder' => [['{{ occupation }}.' => '{{ occupation }} in {{ region }}.'], 'the body uses'],
    'a placeholder without spaces' => [['{{ occupation }}' => '{{occupation}}'], 'with one space inside the braces'],
]);

it('rejects a schema that is not a JSON object', function () {
    $schemas = sys_get_temp_dir().'/work-atlas-schemas-'.bin2hex(random_bytes(8));
    mkdir("{$schemas}/test", recursive: true);
    file_put_contents("{$schemas}/test/sample.v1.json", '[1, 2]');
    register_shutdown_function(fn () => exec('rm -rf '.escapeshellarg($schemas)));

    expect(fn () => (new PromptRegistry(base_path('tests/Fixtures/prompts'), $schemas))->get('test/sample', 1))
        ->toThrow(InvalidPrompt::class, 'is not a JSON object');
});

it('reads a key and a schema path with a hyphen in the name', function () {
    $prompt = registryWith(promptFile(), 'taxonomy/expand-unit-group.v1.md')->get('taxonomy/expand-unit-group', 1);

    expect($prompt->key)->toBe('taxonomy/expand-unit-group');
});

it('reads the committed list-expansion prompt and its schema', function () {
    $prompt = app(PromptRegistry::class)->get('taxonomy/expand-unit-group', 1);

    expect($prompt->variables)->toBe(['isco_code', 'group_title_en', 'group_title_bn', 'hierarchy', 'existing_occupations'])
        ->and(json_decode($prompt->schemaJson, true)['required'])->toBe(['occupations', 'note']);
});
