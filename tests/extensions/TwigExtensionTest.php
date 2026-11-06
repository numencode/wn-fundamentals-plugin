<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\Extensions;

use Cache;
use Detection\MobileDetect;
use Illuminate\Support\Collection;
use NumenCode\Fundamentals\Extensions\TwigExtension;
use NumenCode\Fundamentals\Tests\FundamentalsTestCase;
use Symfony\Component\VarDumper\VarDumper;

class TwigExtensionTest extends FundamentalsTestCase
{
    protected const CSS = 'assets/css/app.css';

    public function setUp(): void
    {
        parent::setUp();

        $this->useFixtureTheme();
    }

    protected function render(string $template, array $vars = []): string
    {
        return $this->app->make('twig.environment')->createTemplate($template)->render($vars);
    }

    protected function cssPath(): string
    {
        return plugins_path('numencode/fundamentals/tests/fixtures/themes/fundamentals/' . self::CSS);
    }

    public function testRegisteredFiltersAndFunctions(): void
    {
        $this->assertSame(['resize_images', 'url_path'], array_keys(TwigExtension::filters()));
        $this->assertSame(
            ['asset_hash', 'class_basename', 'collect', 'd', 'dd', 'detect', 'device_type'],
            array_keys(TwigExtension::functions())
        );
    }

    public function testRemovedFunctionsAreNotRegistered(): void
    {
        $registered = array_merge(array_keys(TwigExtension::filters()), array_keys(TwigExtension::functions()));

        foreach (['app', 'config', 'require', 'trans', 'trim', 'url_params', 'str_pad', 'resize'] as $name) {
            $this->assertNotContains($name, $registered);
        }
    }

    public function testUrlPath(): void
    {
        $this->assertSame('/a/b', $this->render("{{ 'https://example.com/a/b?c=1'|url_path }}"));
        $this->assertSame('', $this->render('{{ nothing|url_path }}'));
        $this->assertSame('', TwigExtension::urlPath(null));
    }

    public function testClassBasenameAndCollect(): void
    {
        $this->assertSame('Gallery', $this->render('{{ class_basename(name) }}', ['name' => 'Acme\Demo\Gallery']));
        $this->assertSame('1,2,3', $this->render('{{ collect([3, 1, 2]).sort().implode(",") }}'));
        $this->assertInstanceOf(Collection::class, TwigExtension::collect([1]));
    }

    public function testAssetHashOfAFileChangesWithItsModificationTime(): void
    {
        $path = $this->cssPath();
        $mtime = filemtime($path);

        $hash = TwigExtension::assetHash(self::CSS);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{10}$/', $hash);
        $this->assertSame($hash, TwigExtension::assetHash('plugins/numencode/fundamentals/tests/fixtures/themes/fundamentals/' . self::CSS), 'Root-relative path');
        $this->assertSame($hash, TwigExtension::assetHash(url('plugins/numencode/fundamentals/tests/fixtures/themes/fundamentals/' . self::CSS)), 'URL');

        try {
            touch($path, $mtime + 10);
            clearstatcache();

            $this->assertNotSame($hash, TwigExtension::assetHash(self::CSS));
        } finally {
            touch($path, $mtime);
            clearstatcache();
        }
    }

    public function testAssetHashFallsBackToTheDeployToken(): void
    {
        Cache::forget(TwigExtension::ASSET_HASH_CACHE_KEY);

        $token = TwigExtension::assetHash();

        $this->assertMatchesRegularExpression('/^\d{14}$/', $token);
        $this->assertSame($token, TwigExtension::assetHash('missing.css'));
        $this->assertSame($token, TwigExtension::assetHash('../../../../outside.txt'), 'Files outside the project are ignored');
    }

    public function testDumpOnlyOutputsInDebugMode(): void
    {
        $dumped = [];
        $previous = VarDumper::setHandler(function ($value) use (&$dumped) {
            $dumped[] = $value;
        });

        try {
            config(['app.debug' => false]);
            $this->render('{{ d("secret") }}');
            $this->assertSame([], $dumped);

            config(['app.debug' => true]);
            $this->render('{{ d("visible") }}');
            $this->assertSame(['visible'], $dumped);
        } finally {
            VarDumper::setHandler($previous);
        }
    }

    public function testDumpAndDieDoesNothingWithoutDebugMode(): void
    {
        config(['app.debug' => false]);

        $this->assertSame('after', $this->render('{{ dd("secret") }}after'));
    }

    public function testDeviceType(): void
    {
        $detect = app(MobileDetect::class);

        $this->assertSame($detect, TwigExtension::detect(), 'One instance per request');

        $detect->setUserAgent('');
        $this->assertSame('desktop', $this->render('{{ device_type() }}'));

        $detect->setUserAgent('Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148');
        $this->assertSame('tablet', $this->render('{{ device_type() }}'));

        $detect->setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148');
        $this->assertSame('mobile|yes', $this->render("{{ device_type() }}|{{ detect().isiOS() ? 'yes' }}"));
    }
}
