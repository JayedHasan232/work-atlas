<?php

namespace App\Modules\Publishing;

use Symfony\Component\Yaml\Yaml;

/**
 * The OpenAPI 3.1 description of the public API v1 (PLAN M21, D76). It is written by hand next to the
 * code (`resources/api/openapi.v1.yaml`); OpenApiSpecTest checks that it covers every route and that
 * real responses match its schemas, so the two cannot drift apart.
 */
final class OpenApiSpec
{
    public const string FILE = 'resources/api/openapi.v1.yaml';

    /**
     * The spec with this server's address.
     *
     * @return array<string, mixed>
     */
    public function document(): array
    {
        $spec = Yaml::parseFile(base_path(self::FILE));

        if (! is_array($spec)) {
            return [];
        }

        $document = [];

        foreach ($spec as $key => $value) {
            $document[(string) $key] = $value;
        }

        $document['servers'] = [['url' => url('/api/v1')]];

        return $document;
    }

    /**
     * Every operation, for the docs page: method, path, operation id and parameters.
     *
     * @return list<array{method: string, path: string, operationId: string, parameters: list<array{name: string, in: string, required: bool}>}>
     */
    public function operations(): array
    {
        $operations = [];
        $paths = $this->document()['paths'] ?? [];

        foreach (is_array($paths) ? $paths : [] as $path => $methods) {
            foreach (is_array($methods) ? $methods : [] as $method => $operation) {
                if (! is_array($operation) || ! is_string($operation['operationId'] ?? null)) {
                    continue;
                }

                $parameters = [];

                foreach (is_array($operation['parameters'] ?? null) ? $operation['parameters'] : [] as $parameter) {
                    if (is_array($parameter) && is_string($parameter['name'] ?? null) && is_string($parameter['in'] ?? null)) {
                        $parameters[] = ['name' => $parameter['name'], 'in' => $parameter['in'], 'required' => ($parameter['required'] ?? false) === true];
                    }
                }

                $operations[] = [
                    'method' => strtoupper((string) $method),
                    'path' => (string) $path,
                    'operationId' => $operation['operationId'],
                    'parameters' => $parameters,
                ];
            }
        }

        return $operations;
    }
}
