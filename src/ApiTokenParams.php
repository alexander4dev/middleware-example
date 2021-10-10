<?php

declare(strict_types=1);

namespace Arus\Authorization;

use Arus\Authorization\Exception\ApiTokenParamsException;
use Psr\Http\Message\ServerRequestInterface;

use function sprintf;
use function base64_decode;
use function json_decode;
use function json_last_error_msg;

final class ApiTokenParams
{
    public const API_TOKEN_PARAMS_HEADER_NAME = 'X-Api-Token-Params';

    public const API_TOKEN_PARAM_USER_ID = 'user_id';
    public const API_TOKEN_PARAM_USER_GROUP = 'user_group';
    public const API_TOKEN_PARAM_USER_FULL_NAME = 'user_fullname';

    private const EXCEPTION_CANT_GET_TOKEN_PARAMS = 'Can\'t get token params from header "%s"';
    private const EXCEPTION_CANT_BASE64_TOKEN_PARAMS = 'Can\'t "base64_decode" token params header "%s"';
    private const EXCEPTION_CANT_JSON_DECODE_TOKEN_PARAMS = 'Can\'t "json_decode" token params: %s';
    private const EXCEPTION_TOKEN_PARAMS_FIELD_REQUIRED = 'Field "%s" required in token params';

    /**
     * @var string|null
     */
    private $userId;

    /**
     * @var string|null
     */
    private $userGroup;

    /**
     * @var string|null
     */
    private $userFullName;

    /**
     * @param ServerRequestInterface $request
     * @return ApiTokenParams
     * @throws ApiTokenParamsException
     */
    public static function createFromRequest(ServerRequestInterface $request): self
    {
        $tokenParamsHeader = $request->getHeader(self::API_TOKEN_PARAMS_HEADER_NAME)[0] ?? null;

        if (null === $tokenParamsHeader) {
            throw new ApiTokenParamsException(
                sprintf(self::EXCEPTION_CANT_GET_TOKEN_PARAMS, self::API_TOKEN_PARAMS_HEADER_NAME)
            );
        }

        $tokenParamsHeaderDecoded = base64_decode($tokenParamsHeader, true);

        if (false === $tokenParamsHeaderDecoded) {
            throw new ApiTokenParamsException(
                sprintf(self::EXCEPTION_CANT_BASE64_TOKEN_PARAMS, $tokenParamsHeader)
            );
        }

        $tokenParams = json_decode($tokenParamsHeaderDecoded, true);

        if (null === $tokenParams) {
            throw new ApiTokenParamsException(
                sprintf(self::EXCEPTION_CANT_JSON_DECODE_TOKEN_PARAMS, json_last_error_msg())
            );
        }

        $requiredParams = [
            self::API_TOKEN_PARAM_USER_ID,
            self::API_TOKEN_PARAM_USER_GROUP,
            self::API_TOKEN_PARAM_USER_FULL_NAME,
        ];

        foreach ($requiredParams as $requiredParam) {
            if (!array_key_exists($requiredParam, $tokenParams)) {
                throw new ApiTokenParamsException(sprintf(self::EXCEPTION_TOKEN_PARAMS_FIELD_REQUIRED, $requiredParam));
            }
        }

        $apiTokenParams = new self();
        $apiTokenParams->setUserId($tokenParams[self::API_TOKEN_PARAM_USER_ID]);
        $apiTokenParams->setUserGroup($tokenParams[self::API_TOKEN_PARAM_USER_GROUP]);
        $apiTokenParams->setUserFullName($tokenParams[self::API_TOKEN_PARAM_USER_FULL_NAME]);

        return $apiTokenParams;
    }

    /**
     * @param string $userId
     * @return ApiTokenParams
     */
    public function setUserId(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @param string $userGroup
     * @return ApiTokenParams
     */
    public function setUserGroup(string $userGroup): self
    {
        $this->userGroup = $userGroup;

        return $this;
    }

    /**
     * @param string $userFullName
     * @return ApiTokenParams
     */
    public function setUserFullName(string $userFullName): self
    {
        $this->userFullName = $userFullName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * @return string|null
     */
    public function getUserGroup(): ?string
    {
        return $this->userGroup;
    }

    /**
     * @return string|null
     */
    public function getUserFullName(): ?string
    {
        return $this->userFullName;
    }
}
