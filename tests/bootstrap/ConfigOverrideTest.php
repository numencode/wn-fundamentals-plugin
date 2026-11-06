<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\Bootstrap;

use Event;
use NumenCode\Fundamentals\Bootstrap\ConfigOverride;
use NumenCode\Fundamentals\Tests\Fixtures\Models\TestItem;
use NumenCode\Fundamentals\Tests\FundamentalsTestCase;
use System\Traits\ConfigMaker;

class ConfigOverrideTest extends FundamentalsTestCase
{
    protected const FIELDS = '$/numencode/fundamentals/tests/fixtures/models/testitem/fields.yaml';

    public function setUp(): void
    {
        parent::setUp();

        ConfigOverride::flush();
    }

    public function tearDown(): void
    {
        ConfigOverride::flush();

        parent::tearDown();
    }

    protected function loadFields(): object
    {
        $maker = new class {
            use ConfigMaker;
        };

        return $maker->makeConfig(self::FIELDS);
    }

    public function testFilePath(): void
    {
        $this->assertSame('/modules/backend/models/user/fields.yaml', ConfigOverride::filePath('fields.yaml', 'Backend\Models\User'));
        $this->assertSame(
            '/plugins/numencode/fundamentals/tests/fixtures/models/testitem/columns.yaml',
            ConfigOverride::filePath('columns.yaml', '\\' . TestItem::class)
        );
    }

    public function testExtendFieldsChangesTheLoadedConfig(): void
    {
        ConfigOverride::extendFields(TestItem::class, function (array $config) {
            $config['fields']['caption'] = ['label' => 'Caption', 'type' => 'text'];

            return $config;
        });

        $fields = $this->loadFields()->fields;

        $this->assertArrayHasKey('caption', $fields);
        $this->assertArrayHasKey('title', $fields);
    }

    public function testExtendAllReceivesThePath(): void
    {
        $paths = [];

        ConfigOverride::extendAll(function (string $path, array $config) use (&$paths) {
            $paths[] = $path;

            return null;
        });

        $this->loadFields();

        $this->assertContains('/plugins/numencode/fundamentals/tests/fixtures/models/testitem/fields.yaml', $paths);
    }

    public function testApplyReturnsNullWhenNothingChanged(): void
    {
        ConfigOverride::extendColumns(TestItem::class, fn (array $config) => null);

        $this->assertNull(ConfigOverride::apply('/plugins/acme/demo/models/post/fields.yaml', ['a' => 1]));
        $this->assertNull(ConfigOverride::apply(ConfigOverride::filePath('columns.yaml', TestItem::class), ['a' => 1]));
    }

    public function testEmptyYamlFilesAreIgnored(): void
    {
        ConfigOverride::extendAll(fn (string $path, array $config) => $config);

        // An empty YAML file is parsed as null; the listener must not fail on it
        $results = Event::fire('system.extendConfigFile', ['/plugins/acme/demo/empty.yaml', null]);

        $this->assertNotContains(false, $results);
    }
}
