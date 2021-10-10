<?php

declare(strict_types=1);

namespace Arus\Authorization\Tests;

use Arus\Authorization\Exception\ApiTokenParamsException;
use Arus\Authorization\ApiTokenParams;

class ApiTokenParamsTest extends AuthorizationTestCase
{
    /**
     * @return void
     */
    public function testEmptyParamsHeader(): void
    {
        $request = $this->getInitialServerRequest();
        $this->expectException(ApiTokenParamsException::class);
        ApiTokenParams::createFromRequest($request);
    }

    /**
     * @return void
     */
    public function testWrongBase64ParamsHeader(): void
    {
        $request = $this->getInitialServerRequest();
        $wrongRequest = $this->setRequestParamsHeader($request, '#wrongbase64');
        $this->expectException(ApiTokenParamsException::class);
        ApiTokenParams::createFromRequest($wrongRequest);
    }

    /**
     * @return void
     */
    public function testWrongJsonParamsHeader(): void
    {
        $request = $this->getInitialServerRequest();
        $wrongRequest = $this->setRequestParamsHeader($request, base64_encode('wrongjson'));
        $this->expectException(ApiTokenParamsException::class);
        ApiTokenParams::createFromRequest($wrongRequest);
    }

    /**
     * @return void
     */
    public function testWrongParamsHeader(): void
    {
        $request = $this->getInitialServerRequest();
        $wrongRequest = $this->setRequestParamsHeader($request, base64_encode('[]'));
        $this->expectException(ApiTokenParamsException::class);
        ApiTokenParams::createFromRequest($wrongRequest);
    }

    /**
     * @return void
     */
    public function testCreateFromRequest(): void
    {
        $tokenParams = $this->getTokenParamsData();
        $successRequest = $this->getRequestWithTokenParams($tokenParams);
        $apiTokenParams = ApiTokenParams::createFromRequest($successRequest);

        $this->assertEquals($tokenParams['user_id'], $apiTokenParams->getUserId());
        $this->assertEquals($tokenParams['user_group'], $apiTokenParams->getUserGroup());
        $this->assertEquals($tokenParams['user_fullname'], $apiTokenParams->getUserFullName());
    }
}
