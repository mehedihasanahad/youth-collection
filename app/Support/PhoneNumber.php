<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Bangladesh mobile number value object.
 *
 * Canonical storage format is the 11-digit local form: 01XXXXXXXXX.
 * Accepts and normalises the common variants users type:
 *   +8801712345678, 8801712345678, 01712345678, 1712345678,
 *   plus spaces, dashes and parentheses.
 */
final class PhoneNumber
{
    /** Matches a normalised 11-digit local number. */
    public const REGEX = '/^01[3-9]\d{8}$/';

    private function __construct(public readonly string $value) {}

    public static function tryFrom(?string $raw): ?self
    {
        $normalised = self::normalize($raw);

        return $normalised === null ? null : new self($normalised);
    }

    /**
     * Reduce any accepted variant to the canonical 01XXXXXXXXX form.
     * Returns null when the input cannot be a Bangladesh mobile number.
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        // 8801712345678 -> 01712345678
        if (str_starts_with($digits, '880')) {
            $digits = '0'.substr($digits, 3);
        }

        // 1712345678 -> 01712345678
        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0'.$digits;
        }

        return preg_match(self::REGEX, $digits) === 1 ? $digits : null;
    }

    public static function isValid(?string $raw): bool
    {
        return self::normalize($raw) !== null;
    }

    /** International dialling form without a leading plus — required by wa.me links. */
    public function international(): string
    {
        return '880'.substr($this->value, 1);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
