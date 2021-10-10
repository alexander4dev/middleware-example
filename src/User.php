<?php

declare(strict_types=1);

namespace Arus\Authorization;

class User
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $group;

    /**
     * @var string
     */
    private $fullName;

    /**
     * @param string $userId
     * @param string $userGroup
     * @param string $userFullName
     */
    public function __construct(
        string $userId,
        string $userGroup,
        string $userFullName
    ) {
        $this->id = $userId;
        $this->group = $userGroup;
        $this->fullName = $userFullName;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }
}
