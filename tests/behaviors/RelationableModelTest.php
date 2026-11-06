<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\Behaviors;

use NumenCode\Fundamentals\Tests\Fixtures\Models\TestGallery;
use NumenCode\Fundamentals\Tests\Fixtures\Models\TestItem;
use NumenCode\Fundamentals\Tests\FundamentalsTestCase;
use Winter\Storm\Exception\ValidationException;

class RelationableModelTest extends FundamentalsTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->loadTranslate();
        $this->createFixtureTables();
    }

    protected function makeGallery(array $items): TestGallery
    {
        $gallery = new TestGallery(['title' => 'Gallery']);
        $gallery->items_list = $items;
        $gallery->save();

        return $gallery;
    }

    /**
     * @return \Illuminate\Support\Collection<int, TestItem>
     */
    protected function itemsOf(TestGallery $gallery)
    {
        return TestItem::withUnpublished()->where('gallery_id', $gallery->id)->orderBy('sort_order')->get();
    }

    public function testCreatesRelatedRecordsInOrder(): void
    {
        $gallery = $this->makeGallery([
            ['title' => 'First', 'subtitle' => 'a'],
            ['title' => 'Second', 'is_published' => 0],
        ]);

        $items = $this->itemsOf($gallery);

        $this->assertSame(['First', 'Second'], $items->pluck('title')->all());
        $this->assertSame([1, 2], $items->pluck('sort_order')->map(fn ($order) => (int) $order)->all());
        $this->assertFalse($items[1]->isPublished());
    }

    public function testGetterReturnsRelatedRecordsAsArrays(): void
    {
        $gallery = $this->makeGallery([['title' => 'First'], ['title' => 'Second']]);

        $value = TestGallery::find($gallery->id)->items_list;

        $this->assertCount(2, $value);
        $this->assertSame('First', $value[0]['title']);
        $this->assertArrayHasKey('id', $value[0]);
    }

    public function testUpdatesInPlaceReordersCreatesAndDeletes(): void
    {
        $gallery = $this->makeGallery([['title' => 'A'], ['title' => 'B'], ['title' => 'C']]);
        [$a, $b, $c] = $this->itemsOf($gallery)->all();

        $gallery = TestGallery::find($gallery->id);
        $gallery->items_list = [
            ['id' => $c->id, 'title' => 'C edited'],
            ['id' => $a->id, 'title' => 'A'],
            ['title' => 'D'],
        ];
        $gallery->save();

        $items = $this->itemsOf($gallery);

        $this->assertSame([$c->id, $a->id], $items->take(2)->pluck('id')->all());
        $this->assertSame(['C edited', 'A', 'D'], $items->pluck('title')->all());
        $this->assertNull(TestItem::withUnpublished()->find($b->id), 'Removed items are deleted');
    }

    public function testEmptyValueDeletesAllRelatedRecords(): void
    {
        $gallery = $this->makeGallery([['title' => 'A'], ['title' => 'B']]);

        $gallery->items_list = null;
        $gallery->save();

        $this->assertCount(0, $this->itemsOf($gallery));
    }

    public function testIdOfAnotherParentCreatesNewRecord(): void
    {
        $other = $this->makeGallery([['title' => 'Foreign']]);
        $foreign = $this->itemsOf($other)->first();

        $gallery = $this->makeGallery([['id' => $foreign->id, 'title' => 'Hijack']]);

        $this->assertSame('Foreign', TestItem::find($foreign->id)->title);
        $this->assertSame($other->id, (int) TestItem::find($foreign->id)->gallery_id);
        $this->assertNotSame($foreign->id, $this->itemsOf($gallery)->first()->id);
    }

    public function testKeepsUnpublishedRecordsWhenTheScopeIsActive(): void
    {
        $gallery = $this->makeGallery([['title' => 'Hidden', 'is_published' => 0], ['title' => 'Visible']]);
        [$hidden, $visible] = $this->itemsOf($gallery)->all();

        // Unit tests without a backend user: the Publishable scope hides unpublished records
        $this->assertSame(1, TestItem::where('gallery_id', $gallery->id)->count());

        $gallery = TestGallery::find($gallery->id);
        $gallery->items_list = [
            ['id' => $hidden->id, 'title' => 'Hidden edited', 'is_published' => 0],
            ['id' => $visible->id, 'title' => 'Visible'],
        ];
        $gallery->save();

        $items = $this->itemsOf($gallery);

        $this->assertCount(2, $items);
        $this->assertSame('Hidden edited', TestItem::withUnpublished()->find($hidden->id)->title);
    }

    public function testStoresTranslationsOnTheRelatedRecords(): void
    {
        $gallery = $this->makeGallery([
            ['title' => 'First', 'RLTranslate' => ['sl' => ['title' => 'Prvi', 'gallery_id' => 999]]],
            ['title' => 'Second', 'RLTranslate' => ['sl' => ['title' => 'Drugi']]],
        ]);

        [$first, $second] = $this->itemsOf($gallery)->all();

        $this->assertSame('Prvi', $first->setTranslatableUseFallback(false)->getAttributeTranslated('title', 'sl'));
        $this->assertSame('Drugi', $second->setTranslatableUseFallback(false)->getAttributeTranslated('title', 'sl'));
        $this->assertSame($gallery->id, (int) $first->gallery_id, 'Non-translatable attributes in RLTranslate are ignored');
    }

    public function testValidationErrorNamesTheItemAndSavesNothing(): void
    {
        try {
            $this->makeGallery([['title' => 'OK'], ['title' => '']]);
            $this->fail('A validation exception was expected');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items_list', $e->getFields());
            $this->assertStringStartsWith('Item 2:', $e->getMessage());
        }

        $this->assertSame(0, TestItem::withUnpublished()->count());
    }

    public function testCustomValidationMethodOnTheParentModel(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Too many items.');

        $this->makeGallery(array_fill(0, 6, ['title' => 'Item']));
    }

    public function testRelationableModelLookup(): void
    {
        $gallery = $this->makeGallery([['title' => 'A']]);
        $item = $this->itemsOf($gallery)->first();
        $gallery = TestGallery::find($gallery->id);

        $this->assertSame('items', $gallery->getRelationable('items_list'));
        $this->assertNull($gallery->getRelationable('unknown'));
        $this->assertSame($item->id, $gallery->getRelationableModel('items_list', ['id' => $item->id])->id);
        $this->assertFalse($gallery->getRelationableModel('items_list', ['id' => 999])->exists);
        $this->assertNull($gallery->getRelationableModel('unknown'));
    }
}
