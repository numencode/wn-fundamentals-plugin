<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\Classes;

use Backend\Facades\BackendAuth;
use Backend\Models\User;
use Backend\Models\UserGroup;
use NumenCode\Fundamentals\Classes\CmsPermissions;
use NumenCode\Fundamentals\Tests\FundamentalsTestCase;
use UnexpectedValueException;

class CmsPermissionsTest extends FundamentalsTestCase
{
    protected const POSTS = 'Acme\Demo\Controllers\Posts';

    public function setUp(): void
    {
        parent::setUp();

        CmsPermissions::flush();
    }

    public function tearDown(): void
    {
        CmsPermissions::flush();

        parent::tearDown();
    }

    protected function loginWithGroups(string ...$codes): void
    {
        $user = new User();
        $user->setRelation('groups', collect(array_map(fn ($code) => new UserGroup(['code' => $code]), $codes)));

        BackendAuth::setUser($user);
    }

    public function testEverythingIsAllowedByDefault(): void
    {
        $this->loginWithGroups('owners');

        foreach (CmsPermissions::ACTIONS as $action) {
            $this->assertTrue(CmsPermissions::can($action, self::POSTS));
        }
    }

    public function testRevokedActionsAreDeniedForTheGroup(): void
    {
        CmsPermissions::revokeCreate('owners', self::POSTS);
        CmsPermissions::revokeUpdate('owners', self::POSTS);
        CmsPermissions::revokeDelete(['owners', 'publishers'], self::POSTS);
        CmsPermissions::revokeDuplicate('publishers', '\\' . self::POSTS);

        $this->loginWithGroups('publishers');

        $this->assertTrue(CmsPermissions::canCreate(self::POSTS));
        $this->assertTrue(CmsPermissions::canUpdate(self::POSTS));
        $this->assertFalse(CmsPermissions::canDelete(self::POSTS));
        $this->assertFalse(CmsPermissions::canDuplicate(self::POSTS), 'A leading backslash is ignored');

        $this->loginWithGroups('owners');

        $this->assertFalse(CmsPermissions::canCreate(self::POSTS));
        $this->assertFalse(CmsPermissions::canUpdate(self::POSTS));
        $this->assertTrue(CmsPermissions::canDuplicate(self::POSTS));
    }

    public function testAnyRevokedGroupDeniesTheAction(): void
    {
        CmsPermissions::revokeDelete('publishers', self::POSTS);

        $this->loginWithGroups('owners', 'publishers');

        $this->assertFalse(CmsPermissions::canDelete(self::POSTS));
    }

    public function testOtherControllersAreNotAffected(): void
    {
        CmsPermissions::revokeDelete('owners', self::POSTS);

        $this->loginWithGroups('owners');

        $this->assertTrue(CmsPermissions::canDelete('Acme\Demo\Controllers\Categories'));
    }

    public function testControllerObjectsAreAccepted(): void
    {
        $controller = new \Backend\Classes\Controller();
        CmsPermissions::revokeDelete('owners', get_class($controller));

        $this->loginWithGroups('owners');

        $this->assertFalse(CmsPermissions::canDelete($controller));
    }

    public function testWithoutUserEverythingIsAllowed(): void
    {
        CmsPermissions::revokeDelete('owners', self::POSTS);

        $this->assertTrue(CmsPermissions::canDelete(self::POSTS));
    }

    public function testUnknownActionIsRejected(): void
    {
        $this->expectException(UnexpectedValueException::class);

        CmsPermissions::can('publish', self::POSTS);
    }
}
