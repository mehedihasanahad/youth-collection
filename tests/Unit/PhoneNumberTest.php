<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function numbers(): array
    {
        return [
            'local form' => ['01712345678', '01712345678'],
            'country code' => ['8801712345678', '01712345678'],
            'plus country code' => ['+8801712345678', '01712345678'],
            'no leading zero' => ['1712345678', '01712345678'],
            'spaces and dashes' => [' +880 1712-345 678 ', '01712345678'],
            'parentheses' => ['(017) 1234 5678', '01712345678'],
            'invalid operator' => ['01212345678', null],
            'too short' => ['0171234567', null],
            'too long' => ['017123456789', null],
            'not a number' => ['hello', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('numbers')]
    public function test_it_normalises_accepted_variants(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($input));
        $this->assertSame($expected !== null, PhoneNumber::isValid($input));
    }

    public function test_it_exposes_the_international_dialling_form(): void
    {
        $this->assertSame('8801712345678', PhoneNumber::tryFrom('01712345678')?->international());
    }

    public function test_try_from_returns_null_for_invalid_input(): void
    {
        $this->assertNull(PhoneNumber::tryFrom('nope'));
    }
}
