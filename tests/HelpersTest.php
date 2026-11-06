<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests;

use DateTime;
use NumenCode\Fundamentals\Classes\QueryFormatter;
use NumenCode\Fundamentals\Tests\Fixtures\Models\TestItem;

class HelpersTest extends FundamentalsTestCase
{
    public function testPluginExists(): void
    {
        $this->assertTrue(plugin_exists('NumenCode.Fundamentals'));
        $this->assertTrue(plugin_exists('numencode.fundamentals'), 'Plugin codes are case-insensitive');
        $this->assertFalse(plugin_exists('Acme.Missing'));
    }

    public function testExtendClass(): void
    {
        extend_class(TestItem::class, \Winter\Storm\Database\Behaviors\Purgeable::class);

        $this->assertTrue((new TestItem())->isClassExtendedWith(\Winter\Storm\Database\Behaviors\Purgeable::class));
    }

    public function testArrayInsertBeforeKey(): void
    {
        $this->assertSame(
            ['a' => 1, 'b' => 2, 'c' => 3],
            array_insert(['a' => 1, 'c' => 3], 'c', ['b' => 2])
        );
    }

    public function testArrayInsertAtPosition(): void
    {
        $this->assertSame([1, 2, 3, 4], array_insert([1, 2, 4], 2, [3]));
        $this->assertSame([0, 1], array_insert([1], 0, [0]));
    }

    public function testArrayInsertAppendsWhenTheKeyIsMissing(): void
    {
        $this->assertSame(['a' => 1, 'b' => 2], array_insert(['a' => 1], 'missing', ['b' => 2]));
    }

    public function testArrayBefore(): void
    {
        $this->assertSame(
            ['c' => 3, 'a' => 1, 'b' => 2],
            array_before(['a' => 1, 'b' => 2, 'c' => 3], 'a', 'c')
        );
        $this->assertSame(
            ['a' => 1, 'c' => 3, 'b' => 2],
            array_before(['a' => 1, 'b' => 2, 'c' => 3], 'b', 'c')
        );
    }

    public function testArrayBeforeWithIntegerKeysAndMissingKeys(): void
    {
        $this->assertSame([6 => 'y', 5 => 'x'], array_before([5 => 'x', 6 => 'y'], '5', '6'));
        $this->assertSame(['a' => 1], array_before(['a' => 1], 'a', 'missing'));
        $this->assertSame(['a' => 1, 'b' => 2], array_before(['a' => 1, 'b' => 2], 'a', 'a'));
    }

    public function testArraySearchRecursive(): void
    {
        $config = ['fields' => ['title' => ['label' => 'Title']], 'tabs' => ['fields' => ['content' => ['label' => 'Content']]]];

        $this->assertSame(['fields', 'title', 'label'], array_search_recursive('Title', $config));
        $this->assertSame(['tabs', 'fields', 'content', 'label'], array_search_recursive('Content', $config));
        $this->assertSame([], array_search_recursive('Missing', $config));
        $this->assertSame([], array_search_recursive(1, ['a' => '1']), 'Strict comparison');
    }

    public function testQueryFormatterInterpolatesBindings(): void
    {
        $sql = QueryFormatter::interpolate(
            'select * from t where a = ? and b = ? and c = ? and d = ? and e = ?',
            ['x?y', null, 5, true, new DateTime('2026-01-02 03:04:05')]
        );

        $this->assertSame("select * from t where a = 'x?y' and b = NULL and c = 5 and d = 1 and e = '2026-01-02 03:04:05'", $sql);
    }

    public function testQueryFormatterEscapesQuotesAndKeepsMissingPlaceholders(): void
    {
        $this->assertSame("a = 'O''Reilly' and b = ?", QueryFormatter::interpolate('a = ? and b = ?', ["O'Reilly"]));
    }

    public function testRemovedHelpersAreGone(): void
    {
        foreach (['numencode_partial', 'validate_request', 'select_options', 'array_merge_reference', 'round_global', 'dumpbug', 'diebug'] as $function) {
            $this->assertFalse(function_exists($function), $function . '() should not exist');
        }
    }
}
