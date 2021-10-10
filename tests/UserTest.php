<?php

declare(strict_types=1);

namespace Arus\Authorization\Tests;

use Arus\Authorization\User;

class UserTest extends AuthorizationTestCase
{
    /**
     * @return void
     */
    public function testUserConstructor(): void
    {
        $userId = 'testid';
        $userGroup = 'testgroup';
        $userFullName = 'testfullname';

        $user = new User($userId, $userGroup, $userFullName);

        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($userGroup, $user->getGroup());
        $this->assertEquals($userFullName, $user->getFullName());
    }
}
