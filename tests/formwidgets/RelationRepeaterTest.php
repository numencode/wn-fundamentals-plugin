<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\FormWidgets;

use Backend\Classes\Controller;
use Backend\Classes\WidgetManager;
use Backend\FormWidgets\Repeater;
use Backend\Widgets\Form;
use NumenCode\Fundamentals\Bootstrap\TranslateIntegration;
use NumenCode\Fundamentals\Classes\RepeaterForm;
use NumenCode\Fundamentals\FormWidgets\RelationRepeater;
use NumenCode\Fundamentals\Tests\Fixtures\Models\TestGallery;
use NumenCode\Fundamentals\Tests\Fixtures\Models\TestItem;
use NumenCode\Fundamentals\Tests\FundamentalsTestCase;
use ReflectionProperty;
use Winter\Translate\Models\Locale;

class RelationRepeaterTest extends FundamentalsTestCase
{
    protected const ITEM_FORM = '$/numencode/fundamentals/tests/fixtures/models/testitem/fields.yaml';

    public function setUp(): void
    {
        parent::setUp();

        $this->loadTranslate();
        $this->createFixtureTables();
    }

    /**
     * Creates a gallery with two items; the second one has a Slovenian translation.
     */
    protected function makeGallery(): TestGallery
    {
        $gallery = new TestGallery(['title' => 'Gallery']);
        $gallery->items_list = [
            ['title' => 'Alpha'],
            ['title' => 'Beta', 'RLTranslate' => ['sl' => ['title' => 'Beta SL']]],
        ];
        $gallery->save();

        return TestGallery::find($gallery->id);
    }

    /**
     * Builds the backend form of a gallery. With $post, the request is a POST with that data (like a save).
     */
    protected function makeForm(TestGallery $gallery, ?array $post = null, string $context = 'update'): Form
    {
        if ($post !== null) {
            $this->app['request']->setMethod('POST');
            $this->app['request']->request->replace($post);
        }

        $form = new Form(new Controller(), [
            'model'     => $gallery,
            'arrayName' => 'TestGallery',
            'context'   => $context,
            'fields'    => [
                'title'      => ['label' => 'Title', 'type' => 'text'],
                'items_list' => ['label' => 'Items', 'type' => 'relationrepeater', 'titleFrom' => 'title', 'form' => self::ITEM_FORM],
            ],
        ]);

        $form->previewMode = $context === 'preview';
        $form->bindToController();

        return $form;
    }

    /**
     * Saves the form like the FormController does (simplified).
     */
    protected function saveForm(Form $form, TestGallery $gallery): void
    {
        foreach ($form->getSaveData() as $attribute => $value) {
            $gallery->$attribute = $value;
        }

        $gallery->save();
    }

    /**
     * @return array<int, RepeaterForm>
     */
    protected function itemForms(Form $form): array
    {
        $repeater = $form->getFormWidget('items_list');
        $property = new ReflectionProperty($repeater, 'formWidgets');

        return $property->getValue($repeater);
    }

    protected function translated(TestItem $item, string $attribute, string $locale): ?string
    {
        return $item->setTranslatableUseFallback(false)->getAttributeTranslated($attribute, $locale);
    }

    public function testWidgetIsRegisteredWithoutOverridingTheCoreRepeater(): void
    {
        $manager = WidgetManager::instance();

        $this->assertSame(RelationRepeater::class, $manager->resolveFormWidget('relationrepeater'));
        $this->assertNotSame(RelationRepeater::class, $manager->resolveFormWidget('repeater'));
    }

    public function testItemFormsAreBoundToTheRelatedRecords(): void
    {
        $gallery = $this->makeGallery();
        $form = $this->makeForm($gallery);

        $this->assertInstanceOf(RelationRepeater::class, $form->getFormWidget('items_list'));

        $models = array_map(fn (RepeaterForm $itemForm) => $itemForm->model, $this->itemForms($form));
        $expected = $gallery->items->pluck('id')->all();

        $this->assertSame($expected, array_map(fn ($model) => $model->id, $models));
    }

    public function testItemFieldsAreMultilingualAndNamedPerItem(): void
    {
        $html = $this->makeForm($this->makeGallery())->render();

        $this->assertStringContainsString('name="TestGallery[items_list][1][RLTranslate][sl][title]"', $html);
        $this->assertStringContainsString('Beta SL', $html, 'Each item shows the translations of its own record');
        $this->assertStringNotContainsString('name="RLTranslate[sl][subtitle]"', $html, 'Item translations are not posted at the top level');
        $this->assertStringContainsString('name="RLTranslate[sl][title]"', $html, 'The parent title is translated by Translate itself');
    }

    /**
     * Regression test: Translate's multilingual widgets inside the items must not store the parent form's
     * (top-level) translations on the item records.
     */
    public function testSavingTheFormStoresEachItemsOwnTranslations(): void
    {
        $gallery = $this->makeGallery();
        [$alpha, $beta] = $gallery->items->all();

        $post = [
            'formItemsList_loaded' => 1,
            'TestGallery' => [
                'title' => 'Gallery EN',
                'items_list' => [
                    ['id' => $beta->id, 'title' => 'Beta EN', 'RLTranslate' => ['en' => ['title' => 'Beta EN'], 'sl' => ['title' => 'Beta SL edited']]],
                    ['id' => $alpha->id, 'title' => 'Alpha EN', 'RLTranslate' => ['en' => ['title' => 'Alpha EN'], 'sl' => ['title' => 'Alfa SL']]],
                    ['title' => 'Gamma EN', 'RLTranslate' => ['en' => ['title' => 'Gamma EN'], 'sl' => ['title' => 'Gama SL']]],
                ],
            ],
            // The parent's own translations, posted at the top level by Translate
            'RLTranslate' => ['en' => ['title' => 'Gallery EN'], 'sl' => ['title' => 'Galerija SL']],
        ];

        $form = $this->makeForm($gallery, $post);
        $saveData = $form->getSaveData();

        // Each item keeps its own posted value and translations (not the parent's top-level ones)
        $this->assertSame('Beta EN', $saveData['items_list'][0]['title']);
        $this->assertSame('Beta SL edited', $saveData['items_list'][0]['RLTranslate']['sl']['title']);

        $this->saveForm($form, $gallery);

        $items = TestItem::withUnpublished()->where('gallery_id', $gallery->id)->orderBy('sort_order')->get();

        $this->assertSame([$beta->id, $alpha->id], $items->take(2)->pluck('id')->all(), 'Posted order, existing records updated in place');
        $this->assertSame(['Beta EN', 'Alpha EN', 'Gamma EN'], $items->pluck('title')->all());
        $this->assertSame(['Beta SL edited', 'Alfa SL', 'Gama SL'], $items->map(fn ($item) => $this->translated($item, 'title', 'sl'))->all());
        $this->assertSame('Galerija SL', $gallery->fresh()->setTranslatableUseFallback(false)->getAttributeTranslated('title', 'sl'));
    }

    public function testPreviewModeIsPassedToTheItemForms(): void
    {
        $form = $this->makeForm($this->makeGallery(), null, 'preview');
        $form->render();

        foreach ($this->itemForms($form) as $itemForm) {
            $this->assertTrue($itemForm->previewMode);
        }
    }

    public function testCoreRepeaterFieldsAreNotMadeMultilingual(): void
    {
        WidgetManager::instance()->registerFormWidget(Repeater::class, ['code' => 'repeater']);

        $form = new Form(new Controller(), [
            'model'     => new TestGallery(),
            'arrayName' => 'TestGallery',
            'data'      => ['extra' => [['title' => 'Nested']]],
            'fields'    => ['extra' => ['type' => 'repeater', 'form' => ['fields' => ['title' => ['type' => 'text']]]]],
        ]);
        $form->bindToController();

        $this->assertInstanceOf(Repeater::class, $form->getFormWidget('extra'));
        $this->assertStringNotContainsString('RLTranslate', $form->render());
    }

    public function testLanguageSelectorIsTheFirstFieldOfTranslatableForms(): void
    {
        $form = $this->makeForm($this->makeGallery());

        $this->assertSame(TranslateIntegration::SELECTOR_FIELD, array_key_first($form->getFields()));
        $this->assertStringContainsString('data-control="numencode-translate-selector"', $form->render());
    }

    public function testLanguageSelectorIsNotAddedToPreviewsOrWithOneLocale(): void
    {
        $gallery = $this->makeGallery();

        $this->assertArrayNotHasKey(TranslateIntegration::SELECTOR_FIELD, $this->makeForm($gallery, null, 'preview')->getFields());

        Locale::where('code', 'sl')->update(['is_enabled' => false]);
        Locale::clearCache();

        $this->assertArrayNotHasKey(TranslateIntegration::SELECTOR_FIELD, $this->makeForm($gallery)->getFields());
    }

    public function testTranslateWidgetsAreDetected(): void
    {
        $html = $this->makeForm($this->makeGallery())->render();
        $this->assertNotEmpty($html);

        $form = $this->makeForm($this->makeGallery());
        $itemForm = $this->itemForms($form)[0];

        $this->assertTrue(RepeaterForm::isTranslateWidget($itemForm->getFormWidget('title')));
        $this->assertFalse(RepeaterForm::isTranslateWidget($form->getFormWidget('items_list')));
    }
}
