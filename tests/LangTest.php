<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests;

use Winter\Storm\Support\Arr;

class LangTest extends FundamentalsTestCase
{
    /**
     * @return array<int, string>
     */
    protected function keys(string $locale): array
    {
        $keys = array_keys(Arr::dot(require __DIR__ . '/../lang/' . $locale . '/lang.php'));
        sort($keys);

        return $keys;
    }

    public function testAllLanguagesHaveTheSameKeys(): void
    {
        $english = $this->keys('en');

        foreach (glob(__DIR__ . '/../lang/*', GLOB_ONLYDIR) as $directory) {
            $locale = basename($directory);

            $this->assertSame($english, $this->keys($locale), "lang/{$locale} must have the same keys as lang/en");
        }
    }
}
