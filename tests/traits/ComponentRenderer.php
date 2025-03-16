<?php namespace NumenCode\Fundamentals\Tests;

use PluginTestCase;
use Cms\Classes\Theme;
use Cms\Classes\ComponentBase;
use NumenCode\Fundamentals\Traits\ComponentRenderer;

class ComponentRendererTest extends PluginTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock the active theme
        Theme::shouldReceive('getActiveTheme')->andReturnSelf();
        Theme::shouldReceive('getDirName')->andReturn('testtheme');
        Theme::shouldReceive('getPath')->andReturn(function ($path) {
            return base_path($path);
        });
    }

    /**
     * Test that the alias is correctly resolved.
     */
    public function testAliasIsResolvedCorrectly()
    {
        $component = new class extends ComponentBase {
            use ComponentRenderer;
        };

        $component->init();

        $this->assertEquals(strtolower(class_basename($component)), $component->alias);
    }

    /**
     * Test that onRender() returns a default partial when no override is set.
     */
    public function testOnRenderReturnsDefaultPartialWhenNoOverride()
    {
        $component = new class extends ComponentBase {
            use ComponentRenderer;
        };

        $this->assertEquals(' ', $component->onRender());
    }

    /**
     * Test that onRender() correctly uses an overridable layout if specified.
     */
    public function testOnRenderUsesOverridableLayout()
    {
        $component = new class extends ComponentBase {
            use ComponentRenderer;
        };

        ComponentRenderer::$overrideLayout = true;

        $component->property = fn ($key) => $key === 'layout' ? '@custom-layout' : null;

        $this->assertEquals(' ', $component->onRender());
    }

    /**
     * Test that getLayoutOptions() returns the default option when no templates are found.
     */
    public function testGetLayoutOptionsReturnsDefaultWhenNoTemplatesFound()
    {
        $component = new class extends ComponentBase {
            use ComponentRenderer;
        };

        $this->assertEquals(['' => 'Default'], $component->getLayoutOptions());
    }

    /**
     * Test that findTemplateOverrides() correctly handles missing directories.
     */
    public function testFindTemplateOverridesHandlesMissingDirectory()
    {
        $component = new class extends ComponentBase {
            use ComponentRenderer;
        };

        $this->assertEquals([], $component->findTemplateOverrides('missing-folder'));
    }
}
