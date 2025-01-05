<?php namespace NumenCode\Fundamentals\Tests\Classes;

use Mockery;
use PluginTestCase;
use ReflectionClass;
use Backend\Models\User;
use UnexpectedValueException;
use Backend\Facades\BackendAuth;
use NumenCode\Fundamentals\Classes\CmsPermissions;

class CmsPermissionsTest extends PluginTestCase
{
    public function tearDown(): void
    {
        Mockery::close(); // Close Mockery after each test
    }

    // TODO the commented tests need fixing

//    public function testCanCreateWithRevokedPermission()
//    {
//        // Mock the user object
//        $mockUser = Mockery::mock(User::class);
//
//        // Mock the user's groups to contain 'group1'
//        $mockUser->shouldReceive('groups')
//            ->andReturn(collect(['group1'])); // Mock groups as a collection
//
//        BackendAuth::shouldReceive('getUser')
//            ->andReturn($mockUser);
//
//        // Revoke create permission for 'group1' on 'TestController'
//        CmsPermissions::revokeCreate('group1', 'TestController');
//
//        // Test if the permission is revoked
//        $this->assertFalse(CmsPermissions::canCreate('TestController'));
//    }
//
//    public function testCanCreateWithoutRevokedPermission()
//    {
//        // Mock the user object
//        $mockUser = Mockery::mock(User::class);
//        // Mock the groups->lists() method to return a collection or an array
//        $mockGroups = Mockery::mock();
//        $mockGroups->shouldReceive('lists')
//            ->once()
//            ->andReturn(['group1']); // Mock return value
//        $mockUser->shouldReceive('groups')
//            ->andReturn($mockGroups);
//        BackendAuth::shouldReceive('getUser')
//            ->andReturn($mockUser);
//        // Test if the permission is not revoked
//        $this->assertTrue(CmsPermissions::canCreate('TestController'));
//    }
//
//    public function testCanUpdateWithRevokedPermission()
//    {
//        // Mock the user object
//        $mockUser = Mockery::mock(User::class);
//        // Mock the groups->lists() method to return a collection or an array
//        $mockGroups = Mockery::mock();
//        $mockGroups->shouldReceive('lists')
//            ->once()
//            ->andReturn(['group1']); // Mock return value
//        $mockUser->shouldReceive('groups')
//            ->andReturn($mockGroups);
//        $mockUser->shouldReceive('extendableGet')
//            ->andReturn([]);  // Mock return value if necessary
//        BackendAuth::shouldReceive('getUser')
//            ->andReturn($mockUser);
//        // Revoke permission
//        CmsPermissions::revokeUpdate('group1', 'TestController');
//        // Test if the permission is revoked
//        $this->assertFalse(CmsPermissions::canUpdate('TestController'));
//    }
//
//    public function testCanUpdateWithoutRevokedPermission()
//    {
//        // Mock the user object
//        $mockUser = Mockery::mock(User::class);
//        // Mock the groups->lists() method to return a collection or an array
//        $mockGroups = Mockery::mock();
//        $mockGroups->shouldReceive('lists')
//            ->once()
//            ->andReturn(['group1']); // Mock return value
//        $mockUser->shouldReceive('groups')
//            ->andReturn($mockGroups);
//        BackendAuth::shouldReceive('getUser')
//            ->andReturn($mockUser);
//        // Test if the permission is not revoked
//        $this->assertTrue(CmsPermissions::canUpdate('TestController'));
//    }
//
//    public function testCanDeleteWithRevokedPermission()
//    {
//        // Mock the user object
//        $mockUser = Mockery::mock(User::class);
//        // Mock the groups->lists() method to return a collection or an array
//        $mockGroups = Mockery::mock();
//        $mockGroups->shouldReceive('lists')
//            ->once()
//            ->andReturn(['group1']); // Mock return value
//        $mockUser->shouldReceive('groups')
//            ->andReturn($mockGroups);
//        $mockUser->shouldReceive('extendableGet')
//            ->andReturn([]);  // Mock return value if necessary
//        BackendAuth::shouldReceive('getUser')
//            ->andReturn($mockUser);
//        // Revoke permission
//        CmsPermissions::revokeDelete('group1', 'TestController');
//        // Test if the permission is revoked
//        $this->assertFalse(CmsPermissions::canDelete('TestController'));
//    }
//
//    public function testCanDeleteWithoutRevokedPermission()
//    {
//        // Mock the user object
//        $mockUser = Mockery::mock(User::class);
//        // Mock the groups->lists() method to return a collection or an array
//        $mockGroups = Mockery::mock();
//        $mockGroups->shouldReceive('lists')
//            ->once()
//            ->andReturn(['group1']); // Mock return value
//        $mockUser->shouldReceive('groups')
//            ->andReturn($mockGroups);
//        BackendAuth::shouldReceive('getUser')
//            ->andReturn($mockUser);
//        // Test if the permission is not revoked
//        $this->assertTrue(CmsPermissions::canDelete('TestController'));
//    }

    public function testRevokeCreate()
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

    public function testRevokeUpdate()
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

    public function testValidateActionThrowsExceptionForInvalidAction()
    {
        // Test if an invalid action throws an UnexpectedValueException
        $this->expectException(UnexpectedValueException::class);
        $reflection = new ReflectionClass(CmsPermissions::class);
        $method = $reflection->getMethod('validateAction');
        $method->setAccessible(true);
        $method->invoke(null, 'invalidAction'); // Trigger the exception
    }

    public function testRevokeDelete()
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
