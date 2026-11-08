<?php

namespace ntentan\nzemba\backends;

interface Backend
{
    public function insert(string $table, array $data): string;
    public function find(mixed $query): array;
    public function findOne(mixed $query): object;
}