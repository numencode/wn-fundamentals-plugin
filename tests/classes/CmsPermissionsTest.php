<?php namespace NumenCode\Fundamentals\Tests\Classes;

use PluginTestCase;
use ReflectionClass;
use UnexpectedValueException;
use NumenCode\Fundamentals\Classes\CmsPermissions;

class CmsPermissionsTest extends PluginTestCase
{
    public function testRevokeCreate(): void
    {
        CmsPermissions::revokeCreate('group1', 'TestController');

        // Using Reflection to access the protected static property `$revokes`
        $reflection = new ReflectionClass(CmsPermissions::class);
        $property = $reflection->getProperty('revokes');
        $property->setAccessible(true);
        $revokes = $property->getValue();

        // Check if the revoke action has been set correctly
        $this->assertTrue(isset($revokes['create']['group1']['TestController']));
    }

    public function testRevokeUpdate(): void
    {
        CmsPermissions::revokeUpdate('group1', 'TestController');

        // Using Reflection to access the protected static property `$revokes`
        $reflection = new ReflectionClass(CmsPermissions::class);
        $property = $reflection->getProperty('revokes');
        $property->setAccessible(true);
        $revokes = $property->getValue();

        // Check if the revoke action has been set correctly
        $this->assertTrue(isset($revokes['update']['group1']['TestController']));
    }

    public function testValidateActionThrowsExceptionForInvalidAction(): void
    {
        // Test if an invalid action throws an UnexpectedValueException
        $this->expectException(UnexpectedValueException::class);

        $reflection = new ReflectionClass(CmsPermissions::class);
        $method = $reflection->getMethod('validateAction');
        $method->setAccessible(true);
        $method->invoke(null, 'invalidAction'); // Trigger the exception
    }

    public function testRevokeDelete(): void
    {
        CmsPermissions::revokeDelete('group1', 'TestController');

        // Using Reflection to access the protected static property `$revokes`
        $reflection = new ReflectionClass(CmsPermissions::class);
        $property = $reflection->getProperty('revokes');
        $property->setAccessible(true);
        $revokes = $property->getValue();

        // Check if the revoke action has been set correctly
        $this->assertTrue(isset($revokes['delete']['group1']['TestController']));
    }
}
