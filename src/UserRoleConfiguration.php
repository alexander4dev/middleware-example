<?php

declare(strict_types=1);

namespace Arus\Authorization;

class UserRoleConfiguration
{
    public const ROLE_NAME_USER = 'user';
    public const ROLE_NAME_REDACTOR = 'redactor';
    public const ROLE_NAME_ADMINISTRATOR = 'administrator';
    public const ROLE_NAME_GRAND = 'grand';
    public const ROLE_NAME_LOCAL = 'local';

    public const ALLOWED_ROLES = [
        self::ROLE_NAME_USER,
        self::ROLE_NAME_REDACTOR,
        self::ROLE_NAME_ADMINISTRATOR,
        self::ROLE_NAME_GRAND,
        self::ROLE_NAME_LOCAL,
    ];

    public const ROLES_PARENTS = [
        self::ROLE_NAME_USER => [
            self::ROLE_NAME_REDACTOR,
        ],
        self::ROLE_NAME_REDACTOR => [
            self::ROLE_NAME_ADMINISTRATOR,
        ],
        self::ROLE_NAME_ADMINISTRATOR => [
            self::ROLE_NAME_GRAND,
        ],
    ];
}
