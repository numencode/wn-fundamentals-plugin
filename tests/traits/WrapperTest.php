<?php namespace NumenCode\Fundamentals\Tests\Traits;

use PHPUnit\Framework\TestCase;
use NumenCode\Fundamentals\Traits\Wrapper;

/**
 * A simple class to simulate a parent object.
 */
class MockParent
{
    public string $property = '';

    public function method(string $arg): string
    {
        return "Method called with $arg";
    }
}

/**
 * A class that uses the Wrapper trait.
 */
class MockWrapper
{
    use Wrapper;
}

class WrapperTest extends TestCase
{
    public function testPropertyAccess(): void
    {
        $parent = new MockParent();
        $parent->property = 'Parent Property Value';

        $wrapper = new MockWrapper($parent);

        // Test property access through wrapper
        $this->assertSame('Parent Property Value', $wrapper->property);

        // Test setting a property through wrapper
        $wrapper->property = 'New Value';
        $this->assertSame('New Value', $parent->property);
    }

    public function testMethodCall(): void
    {
        $parent = new MockParent();

        $wrapper = new MockWrapper($parent);

        // Test calling a method through wrapper
        $result = $wrapper->method('Test Argument');
        $this->assertSame('Method called with Test Argument', $result);
    }

    public function testPropertyIsset(): void
    {
        $parent = new MockParent();
        $parent->property = 'Some Value';

        $wrapper = new MockWrapper($parent);

        // Test isset on property
        $this->assertTrue(isset($wrapper->property));

        // Test unset a property and check isset
        unset($wrapper->property);
        $this->assertFalse(isset($wrapper->property)); // Should be false because unset has occurred

        // Verify the underlying parent property is also unset
        $this->assertFalse(isset($parent->property)); // Should also be false on the parent
    }

    public function testGetWrappedObject(): void
    {
        $parent = new MockParent();
        $wrapper = new MockWrapper($parent);

        // Test getWrappedObject method
        $this->assertSame($parent, $wrapper->getWrappedObject());
    }
}
