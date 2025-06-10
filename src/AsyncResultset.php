<?php

namespace Milanmadar\CoolioORM;

class AsyncResultset
{
    /** @var array<string, array<string|int, mixed>> */
    private array $resultset;

    /**
     * @param array<string, array<string|int, mixed>> $resultset
     */
    public function __construct(array $resultset)
    {
        $this->resultset = $resultset;
    }

    /**
     * @return array<string, array<string|int, mixed>>
     */
    public function getResultset(): array
    {
        return $this->resultset;
    }

    /**
     * @param string|int $name
     * @return array<string|int, mixed>|null
     */
    public function getResultsByName(string|int $name): ?array
    {
        return $this->resultset[$name] ?? null;
    }

}