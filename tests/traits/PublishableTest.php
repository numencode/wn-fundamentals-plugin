<?php namespace NumenCode\Fundamentals\Tests\Traits;

use PluginTestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use NumenCode\Fundamentals\Traits\Publishable;

class PublishableTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Create a temporary table for testing
        Schema::create('test_publishables', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function tearDown(): void
    {
        // Drop the temporary table
        Schema::dropIfExists('test_publishables');

        parent::tearDown();
    }

    public function testGlobalScopeFiltersUnpublishedRecords(): void
    {
        // Create records
        TestPublishable::create(['title' => 'Published', 'is_published' => true]);
        TestPublishable::create(['title' => 'Unpublished', 'is_published' => false]);

        // Query without modifications
        $records = TestPublishable::all();

        $this->assertCount(1, $records);
        $this->assertEquals('Published', $records->first()->title);
    }

    public function testWithUnpublishedScope(): void
    {
        // Create records
        TestPublishable::create(['title' => 'Published', 'is_published' => true]);
        TestPublishable::create(['title' => 'Unpublished', 'is_published' => false]);

        // Query including unpublished records
        $records = TestPublishable::withUnpublished()->get();

        $this->assertCount(2, $records);
    }

    public function testIsPublishedMethod(): void
    {
        // Create a record
        $record = TestPublishable::create(['title' => 'Published', 'is_published' => true]);

        $this->assertTrue($record->isPublished());

        // Update and test again
        $record->is_published = false;
        $this->assertFalse($record->isPublished());
    }
}

// Define a test model
class TestPublishable extends Model
{
    use Publishable;

    protected $table = 'test_publishables';

    protected $fillable = ['title', 'is_published'];
}
