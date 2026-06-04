<?php

namespace App\Domain\Export;

use InvalidArgumentException;

class ListExportRegistry
{
    /**
     * @return array<string, array{filename: string, permission_route: string, headers: list<string>}>
     */
    public function all(): array
    {
        return config('list_exports', []);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * @return array{filename: string, permission_route: string, headers: list<string>}
     */
    public function get(string $key): array
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Unknown list export key [{$key}].");
        }

        return $this->all()[$key];
    }

    /**
     * @return list<string>
     */
    public function headers(string $key): array
    {
        return $this->get($key)['headers'];
    }

    public function permissionRoute(string $key): string
    {
        return $this->get($key)['permission_route'];
    }

    public function filenamePrefix(string $key): string
    {
        return $this->get($key)['filename'];
    }
}
