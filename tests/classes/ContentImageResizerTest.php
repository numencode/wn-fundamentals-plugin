<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\Classes;

use NumenCode\Fundamentals\Classes\ContentImageResizer;
use NumenCode\Fundamentals\Tests\FundamentalsTestCase;
use System\Classes\MediaLibrary;

class ContentImageResizerTest extends FundamentalsTestCase
{
    protected const FOLDER = 'numencode-fundamentals-test';

    protected string $src;

    public function setUp(): void
    {
        parent::setUp();

        // Stored through the media library, so it lands on the configured media disk
        $image = imagecreatetruecolor(800, 400);
        ob_start();
        imagejpeg($image);
        MediaLibrary::instance()->put(self::FOLDER . '/photo.jpg', (string) ob_get_clean());
        imagedestroy($image);

        $this->src = MediaLibrary::url(self::FOLDER . '/photo.jpg');
    }

    public function tearDown(): void
    {
        MediaLibrary::instance()->deleteFolder(self::FOLDER);

        parent::tearDown();
    }

    protected function tags(string $html): array
    {
        preg_match_all('/<img[^>]*>/', (new ContentImageResizer())->makeResizedUrls($html), $matches);

        return $matches[0];
    }

    public function testResizesMediaImagesWithAPixelWidth(): void
    {
        [$style, $attribute] = $this->tags(
            '<p>Text</p><img src="' . $this->src . '" style="margin: 0; width: 300px;" alt="A &amp; B">'
            . '<img src="' . $this->src . '" width="200">'
        );

        $this->assertStringNotContainsString('src="' . $this->src . '"', $style);
        $this->assertStringContainsString('/resizer/', $style);
        $this->assertStringContainsString('alt="A &amp; B"', $style, 'Other attributes are kept');
        $this->assertStringContainsString('style="margin: 0; width: 300px;"', $style);
        $this->assertStringContainsString('/resizer/', $attribute);
    }

    public function testLeavesOtherImagesUnchanged(): void
    {
        $html = '<img src="' . $this->src . '" style="width: 50%">'
            . '<img src="' . $this->src . '">'
            . '<img src="https://example.com/photo.jpg" width="100">'
            . '<img src="/storage/app/uploads/public/photo.jpg" width="100">'
            . '<img src="' . $this->src . '" width="99999">'
            . '<img alt="no source" width="100">';

        $this->assertSame($html, (new ContentImageResizer())->makeResizedUrls($html));
    }

    public function testHandlesEmptyAndNonStringContent(): void
    {
        $resizer = new ContentImageResizer();

        $this->assertSame('', $resizer->makeResizedUrls(null));
        $this->assertSame('', $resizer->makeResizedUrls(''));
        $this->assertSame('<p>No images</p>', $resizer->makeResizedUrls('<p>No images</p>'));
    }

    public function testIsRegisteredAsTwigFilter(): void
    {
        $html = '<img src="' . $this->src . '" width="120">';

        $output = $this->app->make('twig.environment')->createTemplate('{{ html|resize_images|raw }}')->render(['html' => $html]);

        $this->assertStringContainsString('/resizer/', $output);
    }
}
