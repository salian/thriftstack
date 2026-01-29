<?php

declare(strict_types=1);

final class TokenScopeValidator
{
    /**
     * @param string[] $scopes
     */
    public function hasScope(array $scopes, string $required): bool
    {
        return in_array($required, $scopes, true);
    }

    /**
     * @param string[] $scopes
     */
    public function requireScope(array $scopes, string $required): bool
    {
        return $this->hasScope($scopes, $required);
    }

    /**
     * @return string[]
     */
    public function parseScopes(?string $scopes): array
    {
        if ($scopes === null || trim($scopes) === '') {
            return [];
        }
        $parts = preg_split('/[,\s]+/', $scopes) ?: [];
        $result = [];
        foreach ($parts as $part) {
            $value = trim($part);
            if ($value !== '') {
                $result[] = $value;
            }
        }
        return array_values(array_unique($result));
    }
}
