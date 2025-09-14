<?php
namespace Helpers;

class ValidationHelper
{
    public static function integer($value, float $min = -INF, float $max = INF): int
    {
        $value = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ["min_range" => (int) $min, "max_range" => (int) $max]
        );

        if ($value === false) {
            throw new \InvalidArgumentException("The provided value is not a valid integer.");
        }

        return $value;
    }

    public static function string($value, int $minLen = 1, int $maxLen = 255): string
    {
        $value = trim((string) $value);

        if (strlen($value) < $minLen || strlen($value) > $maxLen) {
            throw new \InvalidArgumentException("String length out of range.");
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
