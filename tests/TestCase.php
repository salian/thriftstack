<?php

declare(strict_types=1);

abstract class TestCase
{
    private int $assertions = 0;

    protected function assertTrue(bool $condition, string $message = 'Expected true'): void
    {
        $this->assertions++;
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    protected function assertFalse(bool $condition, string $message = 'Expected false'): void
    {
        $this->assertions++;
        if ($condition) {
            throw new RuntimeException($message);
        }
    }

    protected function assertEquals($expected, $actual, string $message = 'Values are not equal'): void
    {
        $this->assertions++;
        if ($expected != $actual) {
            throw new RuntimeException($message . " | expected=" . var_export($expected, true) . " actual=" . var_export($actual, true));
        }
    }

    protected function assertNotEmpty($value, string $message = 'Expected non-empty value'): void
    {
        $this->assertions++;
        if (empty($value)) {
            throw new RuntimeException($message);
        }
    }

    public function assertions(): int
    {
        return $this->assertions;
    }
}
