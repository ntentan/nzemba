<?php

namespace ntentan\nzemba\backends;

interface Backend
{
    public function insert(string $table, array $data): string;
    public function find(string $table, mixed $query = null, string $model = \stdClass::class): array;
    public function findOne(string $table, mixed $query = null, string $model = \stdClass::class): ?object;
}