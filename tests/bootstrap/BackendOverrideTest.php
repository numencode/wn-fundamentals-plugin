<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\Bootstrap;

use Backend\Classes\Controller;
use Backend\Classes\ListColumn;
use Backend\Widgets\Form;
use Backend\Widgets\Lists;
use Event;
use NumenCode\Fundamentals\Bootstrap\BackendOverride;
use NumenCode\Fundamentals\Tests\Fixtures\Models\TestItem;
use NumenCode\Fundamentals\Tests\FundamentalsTestCase;

class BackendOverrideTest extends FundamentalsTestCase
{
    protected function switchColumnValue(bool $value): string
    {
        $list = new Lists(new Controller(), ['model' => new TestItem(), 'columns' => [], 'alias' => 'testList']);
        $column = new ListColumn('is_published', 'Published');
        $column->displayAs('switch', []);

        $record = new TestItem();
        $record->is_published = $value;

        return (string) $list->getColumnValue($record, $column);
    }

    public function testFeaturesAreEnabledByDefaultAndCanBeSwitchedOff(): void
    {
        foreach (['switch_column_icons', 'pages_viewbag_primary_tabs', 'assets'] as $feature) {
            $this->assertTrue(BackendOverride::enabled($feature));
        }

        config(['numencode.fundamentals::backend.assets' => false]);

        $this->assertFalse(BackendOverride::enabled('assets'));
    }

    public function testSwitchColumnsShowIconsWithTranslatedLabels(): void
    {
        $this->assertSame('<i class="icon-check list-switch-true"></i> Yes', $this->switchColumnValue(true));
        $this->assertSame('<i class="icon-times list-switch-false"></i> No', $this->switchColumnValue(false));

        $this->app->setLocale('sl');

        $this->assertStringEndsWith(e(trans('backend::lang.list.column_switch_false')), $this->switchColumnValue(false));
    }

    public function testSwitchColumnsAreUnchangedWhenSwitchedOff(): void
    {
        Event::forget('backend.list.overrideColumnValue');
        config(['numencode.fundamentals::backend.switch_column_icons' => false]);
        (new BackendOverride())->init();

        $this->assertSame('No', $this->switchColumnValue(false));
    }

    public function testPagesSettingsAreMovedToThePrimaryTabs(): void
    {
        if (!class_exists(\Winter\Pages\Classes\Page::class)) {
            $this->markTestSkipped('Winter.Pages is not installed.');
        }

        $form = new Form(new Controller(), [
            'model'         => new \Winter\Pages\Classes\Page(),
            'tabs'          => ['fields' => []],
            'secondaryTabs' => ['fields' => [
                'viewBag[title]' => ['label' => 'Title', 'cssClass' => 'secondary-tab extra'],
                'markup'         => ['label' => 'Markup'],
            ]],
        ]);

        Event::fire('backend.form.extendFieldsBefore', [$form]);

        $this->assertArrayHasKey('viewBag[title]', $form->tabs['fields']);
        $this->assertSame('extra', $form->tabs['fields']['viewBag[title]']['cssClass']);
        $this->assertArrayNotHasKey('viewBag[title]', $form->secondaryTabs['fields']);
        $this->assertArrayHasKey('markup', $form->secondaryTabs['fields']);
    }

    public function testOtherFormsAreNotChanged(): void
    {
        $form = new Form(new Controller(), [
            'model'         => new TestItem(),
            'secondaryTabs' => ['fields' => ['viewBag[title]' => ['label' => 'Title']]],
        ]);

        Event::fire('backend.form.extendFieldsBefore', [$form]);

        $this->assertArrayHasKey('viewBag[title]', $form->secondaryTabs['fields']);
    }
}
