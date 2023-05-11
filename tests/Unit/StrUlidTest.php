<?php

namespace Tests\Unit;

use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class StrUlidTest extends TestCase
{
    /**
     * Str helper ulid method should always return 26 char string.
     */
    public function test_str_ulid_method_should_return_26_char_string(): void
    {
        $this->assertEquals(26, strlen(Str::ulid()));
    }
    /**
     * Str helper ulid method should always return unique string.
     */
    public function test_str_ulid_method_should_return_unique_string(): void
    {
        $str1 = (string) Str::ulid();
        $str2 = (string) Str::ulid();
        $this->assertNotEquals($str1, $str2);
    }
    /**
     * Str ulid string should only have letters and numbers.
     */
    public function test_str_ulid_should_only_have_letters_and_numbers(): void
    {
        $this->assertTrue(ctype_alnum(
            (string) Str::ulid()
        ));
    }
}
