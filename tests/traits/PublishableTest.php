<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\Traits;

use Backend\Facades\BackendAuth;
use Backend\Models\User;
use NumenCode\Fundamentals\Scopes\PublishableScope;
use NumenCode\Fundamentals\Tests\Fixtures\Models\TestItem;
use NumenCode\Fundamentals\Tests\FundamentalsTestCase;

class PublishableTest extends FundamentalsTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->loadTranslate();
        $this->createFixtureTables();

        TestItem::create(['title' => 'Published', 'is_published' => true]);
        TestItem::create(['title' => 'Unpublished', 'is_published' => false]);
    }

    public function testHidesUnpublishedRecordsForVisitors(): void
    {
        $this->assertTrue(PublishableScope::shouldHideUnpublished());
        $this->assertSame(['Published'], TestItem::pluck('title')->all());
    }

    public function testBackendUsersSeeUnpublishedRecords(): void
    {
        BackendAuth::setUser(new User());

        $this->assertFalse(PublishableScope::shouldHideUnpublished());
        $this->assertCount(2, TestItem::all());
    }

    public function testWithUnpublishedAndOnlyUnpublished(): void
    {
        $this->assertCount(2, TestItem::withUnpublished()->get());
        $this->assertSame(['Unpublished'], TestItem::onlyUnpublished()->pluck('title')->all());
    }

    public function testIsPublishedReturnsABoolean(): void
    {
        $this->assertTrue(TestItem::withUnpublished()->where('title', 'Published')->first()->isPublished());
        $this->assertFalse(TestItem::withUnpublished()->where('title', 'Unpublished')->first()->isPublished());
    }

    public function testPublishedColumnCanBeChanged(): void
    {
        $model = new class extends TestItem {
            public const PUBLISHED_COLUMN = 'is_visible';
        };

        $this->assertSame('is_published', (new TestItem())->getPublishedColumn());
        $this->assertSame('is_visible', $model->getPublishedColumn());
        $this->assertSame('nc_test_items.is_visible', $model->getQualifiedPublishedColumn());
    }
}
