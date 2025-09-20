<?php
namespace Helpers;

use Types\ValueType;

class ValidationHelper
{
    public static function integer($value, float $min = -INF, float $max = INF): int
    {
        $value = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ["min_range" => (int)$min, "max_range" => (int)$max]
        );
        if ($value === false) {
            throw new \InvalidArgumentException("The provided value is not a valid integer.");
        }
        return $value;
    }

    public static function string($value, int $minLen = 1, int $maxLen = 255): string
    {
        $value = trim((string)$value);
        if (strlen($value) < $minLen || strlen($value) > $maxLen) {
            throw new \InvalidArgumentException("String length out of range.");
        }
        // NOTE: For raw storage, you often don't HTML-escape here; do it in the view layer.
        // Kept for compatibility with your existing code:
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function validateDate(string $date, string $format = 'Y-m-d'): string
    {
        $d = \DateTime::createFromFormat($format, $date);
        if ($d && $d->format($format) === $date) {
            return $date;
        }
        throw new \InvalidArgumentException(sprintf("Invalid date format for %s. Required format: %s", $date, $format));
    }

    /**
     * Validate multiple fields against a spec of field => ValueType.
     * Returns sanitized/validated associative array.
     */
    public static function validateFields(array $fields, array $data): array
    {
        $validated = [];
        foreach ($fields as $field => $type) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new \InvalidArgumentException("Missing field: $field");
            }
            $value = $data[$field];

            $validated[$field] = match ($type) {
                ValueType::STRING => is_string($value)
                    ? trim($value)
                    : throw new \InvalidArgumentException("Invalid string for $field"),
                ValueType::INT    => self::integer($value),
                ValueType::FLOAT  => filter_var($value, FILTER_VALIDATE_FLOAT) !== false
                    ? (float)$value
                    : throw new \InvalidArgumentException("Invalid float for $field"),
                ValueType::DATE   => self::validateDate((string)$value),
            };
        }
        return $validated;
    }
}

