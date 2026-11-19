<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\Bootstrap;

use NumenCode\Fundamentals\Tests\Fixtures\Models\TestGallery;
use NumenCode\Fundamentals\Tests\FundamentalsTestCase;
use Winter\Storm\Exception\ValidationException;

/**
 * Translations that are only whitespace (an empty rich editor posts "\n") are saved empty.
 */
class BlankTranslationsTest extends FundamentalsTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!class_exists(\Winter\Translate\Plugin::class)) {
            $this->markTestSkipped('Winter.Translate is needed.');
        }

        $this->loadTranslate();
        $this->createFixtureTables();
    }

    protected function gallery(): TestGallery
    {
        $gallery = new TestGallery(['title' => 'Gallery']);
        $gallery->save();

        return TestGallery::find($gallery->id);
    }

    public function testAWhitespaceTranslationIsSavedEmptyAndPassesValidation(): void
    {
        $gallery = $this->gallery();
        $gallery->setAttributeTranslated('title', "\n", 'sl');
        $gallery->save();

        $gallery = TestGallery::find($gallery->id);

        $this->assertSame('', $gallery->getTranslateAttributes('sl')['title'] ?? '', 'Stored empty');
        $this->assertSame('Gallery', $gallery->getAttributeTranslated('title', 'sl'), 'Falls back to the default value');
    }

    public function testOtherTranslationsAreKept(): void
    {
        $gallery = $this->gallery();
        $gallery->setAttributeTranslated('title', " Galerija\n", 'sl');
        $gallery->save();

        $this->assertSame(" Galerija\n", TestGallery::find($gallery->id)->getAttributeTranslated('title', 'sl'));
    }

    public function testTheFeatureCanBeSwitchedOff(): void
    {
        config(['numencode.fundamentals::translate.clear_blank_translations' => false]);

        $gallery = $this->gallery();
        $gallery->setAttributeTranslated('title', "\n", 'sl');

        $this->expectException(ValidationException::class);
        $gallery->save();
    }
}
