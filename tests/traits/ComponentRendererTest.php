<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\Traits;

use Cms\Classes\ComponentManager;
use Cms\Classes\Controller;
use Cms\Classes\Theme;
use NumenCode\Fundamentals\Tests\Fixtures\Components\TestRenderer;
use NumenCode\Fundamentals\Tests\FundamentalsTestCase;

class ComponentRendererTest extends FundamentalsTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->useFixtureTheme();
        ComponentManager::instance()->registerComponent(TestRenderer::class, 'testRenderer');
    }

    public function tearDown(): void
    {
        TestRenderer::$overrideLayout = false;

        parent::tearDown();
    }

    protected function renderPage(): array
    {
        $html = (new Controller(Theme::getActiveTheme()))->run('/renderer')->getContent();

        preg_match('/A\[(.*?)\]\s*B\[(.*?)\]/s', $html, $match);

        return [trim($match[1] ?? ''), trim($match[2] ?? '')];
    }

    public function testRendersTheDefaultPartialWhenLayoutSelectionIsDisabled(): void
    {
        [$first, $second] = $this->renderPage();

        $this->assertSame('DEFAULT Hello', $first);
        $this->assertSame('DEFAULT Hello', $second);
    }

    public function testRendersTheThemeLayoutFromTheClassFolder(): void
    {
        TestRenderer::$overrideLayout = true;

        [$first] = $this->renderPage();

        // partials/testrenderer/fancy.htm – found by class name, although the page alias is "first"
        $this->assertStringContainsString('FANCY', $first);
        $this->assertStringContainsString('greeting=Hello', $first);
    }

    public function testKeepsThePageAliasForSelfAndAjaxHandlers(): void
    {
        TestRenderer::$overrideLayout = true;

        [$first] = $this->renderPage();

        $this->assertStringContainsString('self=first ', $first);
        $this->assertStringContainsString('handler=first::onTest', $first);
    }

    public function testFallsBackToTheComponentsOwnLayout(): void
    {
        TestRenderer::$overrideLayout = true;

        [, $second] = $this->renderPage();

        $this->assertSame('PLAIN Hello', $second);
    }

    public function testLayoutOptionsListComponentAndThemeLayouts(): void
    {
        $component = new TestRenderer();

        $this->assertSame(
            ['@default' => 'Default', '@plain' => 'Plain', '@fancy' => 'Fancy'],
            $component->getLayoutOptions()
        );
    }

    public function testDoesNotChangeTheAliasAndAllowsOwnInit(): void
    {
        $component = ComponentManager::instance()->makeComponent('testRenderer', null, []);
        $component->alias = 'myAlias';
        $component->init();

        $this->assertSame('myAlias', $component->alias);
        $this->assertSame('myAlias', (string) $component);
        $this->assertTrue($component->initialized);
        $this->assertSame('testrenderer', $component->getLayoutFolder());
    }
}
