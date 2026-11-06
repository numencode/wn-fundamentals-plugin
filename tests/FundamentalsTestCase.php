<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests;

use Backend\Classes\WidgetManager;
use Cms\Classes\Theme;
use Illuminate\Database\Schema\Blueprint;
use NumenCode\Fundamentals\Bootstrap\TranslateIntegration;
use Schema;
use System\Classes\MarkupManager;
use System\Tests\Bootstrap\PluginTestCase;
use Winter\Translate\Models\Locale;

/**
 * Base test case with the fixture tables, Winter.Translate and the fixture theme.
 */
abstract class FundamentalsTestCase extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        WidgetManager::forgetInstance();
        MarkupManager::forgetInstance();
    }

    /**
     * Creates the tables of the fixture models (TestGallery, TestItem).
     */
    protected function createFixtureTables(): void
    {
        Schema::create('nc_test_galleries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('nc_test_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('gallery_id')->nullable();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Loads Winter.Translate with the locales "en" (default) and "sl", and (re)initializes the integration,
     * because the Fundamentals plugin booted before Translate was loaded.
     */
    protected function loadTranslate(): void
    {
        $this->instantiatePlugin('Winter.Translate');

        if (!Locale::where('code', 'sl')->exists()) {
            Locale::create(['code' => 'sl', 'name' => 'Slovenian', 'is_enabled' => true]);
        }

        Locale::clearCache();
        WidgetManager::forgetInstance();

        (new TranslateIntegration())->init();
    }

    /**
     * Uses the fixture theme (tests/fixtures/themes/fundamentals) as the active theme.
     */
    protected function useFixtureTheme(): void
    {
        $this->app->setThemesPath(plugins_path('numencode/fundamentals/tests/fixtures/themes'));
        config(['cms.activeTheme' => 'fundamentals']);
        Theme::resetCache();
    }
}
