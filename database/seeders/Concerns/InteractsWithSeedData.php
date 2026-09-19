<?php

namespace Database\Seeders\Concerns;

use RuntimeException;

trait InteractsWithSeedData
{
    /**
     * @return array<string, mixed>
     */
    protected function seedData(): array
    {
        $path = __DIR__.'/../data/seed-data.json';

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Unable to read seed data from {$path}");
        }

        return $decoded;
    }
}
