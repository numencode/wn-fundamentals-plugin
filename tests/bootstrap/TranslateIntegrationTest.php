<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\Bootstrap;

use Cache;
use Cms\Classes\Theme;
use Event;
use Lang;
use NumenCode\Fundamentals\Bootstrap\TranslateIntegration;
use NumenCode\Fundamentals\Tests\FundamentalsTestCase;
use ReflectionMethod;
use Winter\Pages\Classes\Page;
use Winter\Pages\Classes\Snippet;
use Winter\Pages\Classes\SnippetManager;

class TranslateIntegrationTest extends FundamentalsTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!class_exists(\Winter\Pages\Plugin::class) || !class_exists(\Winter\Translate\Plugin::class)) {
            $this->markTestSkipped('Winter.Pages and Winter.Translate are needed.');
        }

        $this->instantiatePlugin('Winter.Pages');
    }

    /**
     * The Pages cache keys (menu tree, snippet map, partial snippet map) of a locale. Translate appends the locale
     * to them through the pages.*.getCacheKey events.
     *
     * @return array<int, string>
     */
    protected function cacheKeys(string $locale): array
    {
        $theme = Theme::getEditTheme();
        $current = Lang::getLocale();
        Lang::setLocale($locale);

        try {
            return array_map(
                fn (array $method) => (new ReflectionMethod($method[0], $method[1]))->invoke(null, $theme),
                [[Page::class, 'getMenuCacheKey'], [Snippet::class, 'getMapCacheKey'], [SnippetManager::class, 'getPartialMapCacheKey']],
            );
        } finally {
            Lang::setLocale($current);
        }
    }

    /**
     * Fills the Pages caches of both locales and returns their keys.
     *
     * @return array<string, array<int, string>>
     */
    protected function fillCaches(): array
    {
        $keys = ['en' => $this->cacheKeys('en'), 'sl' => $this->cacheKeys('sl')];

        $this->assertNotSame($keys['en'], $keys['sl'], 'Translate appends the locale to the Pages cache keys');

        foreach (array_merge(...array_values($keys)) as $key) {
            Cache::forever($key, 'cached');
        }

        return $keys;
    }

    public function testSavingInPagesClearsTheCachesOfAllLocales(): void
    {
        $this->loadTranslate();
        $keys = $this->fillCaches();

        Event::fire('pages.object.save', [null, new Page(), 'page']);

        foreach (array_merge(...array_values($keys)) as $key) {
            $this->assertFalse(Cache::has($key), $key);
        }

        $this->assertSame('en', Lang::getLocale(), 'the locale is restored');
    }

    public function testSavingACmsTemplateClearsTheCachesOfAllLocales(): void
    {
        $this->loadTranslate();
        $keys = $this->fillCaches();

        Event::fire('cms.template.save', [null, new Page(), 'page']);

        foreach ($keys['sl'] as $key) {
            $this->assertFalse(Cache::has($key), $key);
        }
    }

    public function testTheFeatureCanBeSwitchedOff(): void
    {
        // Switched off after the listeners are registered (as when the plugin booted with the default config)
        $this->loadTranslate();
        config(['numencode.fundamentals::translate.pages_cache_all_locales' => false]);
        $keys = $this->fillCaches();

        Event::fire('pages.object.save', [null, new Page(), 'page']);

        foreach ($keys['sl'] as $key) {
            $this->assertTrue(Cache::has($key), $key);
        }

        $this->assertFalse(TranslateIntegration::enabled('pages_cache_all_locales'));
    }

    public function testTheLocaleIsRestoredWhenClearingFails(): void
    {
        $this->loadTranslate();
        Lang::setLocale('sl');

        Event::listen('pages.page.getMenuCacheKey', function () {
            throw new \RuntimeException('Cache failure');
        });

        try {
            TranslateIntegration::clearPagesCacheForAllLocales();
            $this->fail('No exception thrown');
        } catch (\RuntimeException) {
            $this->assertSame('sl', Lang::getLocale());
        }
    }
}
