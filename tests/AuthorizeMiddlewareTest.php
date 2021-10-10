<?php

declare(strict_types=1);

namespace Arus\Authorization\Tests;

use Arus\Authorization\Exception\RuntimeException;
use Arus\Authorization\Exception\ConfigValidationException;
use Arus\Authorization\AuthorizeMiddleware;
use stdClass;

class AuthorizeMiddlewareTest extends AuthorizationTestCase
{
    /**
     * @return void
     */
    public function testWrongRouteObject(): void
    {
        $authorizeMiddleware = new AuthorizeMiddleware();
        $request = $this->getRequestWithRouteObject(null, new stdClass());

        $this->expectException(RuntimeException::class);
        $authorizeMiddleware->process($request, $this->getRequestHandler());
    }

    /**
     * @return void
     */
    public function testWrongTokenParams(): void
    {
        $authorizeMiddleware = new AuthorizeMiddleware();
        $request = $this->getInitialServerRequest();
        $requestWithRoute = $this->getRequestWithRouteObject($request);

        $this->expectException(RuntimeException::class);
        $authorizeMiddleware->process($requestWithRoute, $this->getRequestHandler());
    }

    /**
     * @return void
     */
    public function testWrongConfig(): void
    {
        $this->expectException(ConfigValidationException::class);
        new AuthorizeMiddleware(['wrongroutename' => '']);
    }

    /**
     * @return void
     */
    public function testWrongRole(): void
    {
        $authorizeMiddleware = new AuthorizeMiddleware();
        $tokenParams = $this->getTokenParamsData();
        $tokenParams['user_group'] = 'wrongrolename';
        $requestWithTokenParams = $this->getRequestWithTokenParams($tokenParams);
        $requestWithRoute = $this->getRequestWithRouteObject($requestWithTokenParams);

        $this->expectException(RuntimeException::class);
        $authorizeMiddleware->process($requestWithRoute, $this->getRequestHandler());
    }

    /**
     * @return void
     */
    public function testInvoke(): void
    {
        $request = $this->getRequestWithRouteObject();
        $response = $this->getInitialResponse();
        $callable = $this->getSlimMiddlewareCallable();

        $forbiddenMiddleware = new AuthorizeMiddleware();
        $forbiddenResponse = $forbiddenMiddleware($request, $response, $callable);

        $this->assertTrue($this->isResponseForbidden($forbiddenResponse));

        $successMiddleware = new AuthorizeMiddleware([
            $this->testRouteName => $this->testUserGroup,
        ]);

        $successResponse = $successMiddleware($request, $response, $callable);

        $this->assertTrue($this->isResponseSuccess($successResponse));
    }

    /**
     * @return void
     */
    public function testProcess(): void
    {
        $request = $this->getRequestWithTokenParams();
        $authorizeMiddlewareReflection = $this->getAuthorizeMiddlewareReflection();
        $routeNameAttribute = $authorizeMiddlewareReflection->getConstant('REQUEST_ATTRIBUTE_ROUTE_NAME');
        $requestWithRoute = $request->withAttribute($routeNameAttribute, $this->testRouteName);

        $forbiddenMiddleware = new AuthorizeMiddleware();
        $forbiddenResponse = $forbiddenMiddleware->process($requestWithRoute, $this->getRequestHandler());

        $this->assertTrue($this->isResponseForbidden($forbiddenResponse));

        $successMiddleware = new AuthorizeMiddleware([
            $this->testRouteName => $this->testUserGroup,
        ]);

        $successResponse = $successMiddleware->process($requestWithRoute, $this->getRequestHandler());

        $this->assertTrue($this->isResponseSuccess($successResponse));
    }
}
