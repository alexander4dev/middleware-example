<?php

declare(strict_types=1);

namespace Arus\Authorization\Tests;

use Sunrise\Http\Factory\ResponseFactory;
use Sunrise\Http\Factory\ServerRequestFactory;
use Sunrise\Http\Router\RequestHandler;
use Arus\Authorization\ApiTokenParams;
use Arus\Authorization\AuthorizeMiddleware;
use Arus\Authorization\UserRoleConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class AuthorizationTestCase extends TestCase
{
    /**
     * @var string
     */
    protected $testRouteName = 'testroutename';

    /**
     * @var string
     */
    protected $testUserGroup = 'user';

    /**
     * @var ServerRequestFactoryInterface|null
     */
    private $serverRequestFactory;

    /**
     * @var ServerRequestInterface|null
     */
    private $initialServerRequest;

    /**
     * @var ReflectionClass|null
     */
    private $apiTokenParamsReflection;
    
    /**
     * @var UserRoleConfiguration|null
     */
    private $userRoleConfiguration;
    
    /**
     * @var ReflectionClass|null
     */
    private $userRoleConfigurationReflection;

    /**
     * @var ResponseFactoryInterface|null
     */
    private $responseFactory;

    /**
     * @var ResponseInterface|null
     */
    private $initialResponse;

    /**
     * @var ReflectionClass|null
     */
    private $authorizeMiddlewareReflection;

    /**
     * @var RequestHandlerInterface|null
     */
    private $requestHandler;

    /**
     * @param array $tokenParams
     * @return ServerRequestInterface
     */
    protected function getRequestWithTokenParams(array $tokenParams = null): ServerRequestInterface
    {
        $tokenParamsData = $tokenParams ?? $this->getTokenParamsData();
        $tokenParamsEncoded = base64_encode(json_encode($tokenParamsData));
        $initialRequest = $this->getInitialServerRequest();
        $requestWithTokenParams = $this->setRequestParamsHeader($initialRequest, $tokenParamsEncoded);
        
        return $requestWithTokenParams;
    }

    /**
     * @return array
     */
    protected function getTokenParamsData(): array
    {
        $userId = 'test_user_id';
        $userGroup = $this->testUserGroup;
        $userFullName = 'test_user_fullname';

        $tokenParams = [
            'user_id' => $userId,
            'user_group' => $userGroup,
            'user_fullname' => $userFullName,
        ];

        return $tokenParams;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $headerValue
     * @return ServerRequestInterface
     */
    protected function setRequestParamsHeader(
        ServerRequestInterface $request,
        string $headerValue
    ): ServerRequestInterface {
        $apiTokenParamsReflection = $this->getApiTokenParamsReflection();
        $headerName = $apiTokenParamsReflection->getConstant('API_TOKEN_PARAMS_HEADER_NAME');
        $requestWithHeader = $request->withHeader($headerName, $headerValue);

        return $requestWithHeader;
    }
    
    /**
     * @param ServerRequestInterface $request
     * @param object $route
     * @return ServerRequestInterface
     */
    protected function getRequestWithRouteObject(
        ServerRequestInterface $request = null,
        object $route = null
    ): ServerRequestInterface {
        $initialServerRequest = $request ?? $this->getRequestWithTokenParams();
        $authorizeMiddlewareReflection = $this->getAuthorizeMiddlewareReflection();

        if (null === $route) {
            $routeNameGetter = $authorizeMiddlewareReflection->getConstant(
                'REQUEST_ATTRIBUTE_ROUTE_OBJECT_METHOD_GET_NAME'
            );

            $route = $this->getMockBuilder(stdClass::class)
                    ->setMethods([$routeNameGetter])
                    ->getMock();

            $route->expects($this->any())
                    ->method($routeNameGetter)
                    ->willReturn($this->testRouteName);
        }

        $routeObjectAttribute = $authorizeMiddlewareReflection->getConstant('REQUEST_ATTRIBUTE_ROUTE_OBJECT');
        $serverRequestWithRoute = $initialServerRequest->withAttribute($routeObjectAttribute, $route);

        return $serverRequestWithRoute;
    }
    
    /**
     * @return ReflectionClass
     */
    protected function getApiTokenParamsReflection(): ReflectionClass
    {
        if (!$this->apiTokenParamsReflection instanceof ReflectionClass) {
            $this->apiTokenParamsReflection = new ReflectionClass(ApiTokenParams::class);
        }

        return $this->apiTokenParamsReflection;
    }

    /**
     * @return ServerRequestInterface
     */
    protected function getInitialServerRequest(): ServerRequestInterface
    {
        if (!$this->initialServerRequest instanceof ServerRequestInterface) {
            $requestFactory = $this->getServerRequestFactory();
            $this->initialServerRequest = $requestFactory->createServerRequest('testmethod', 'testuri');
        }

        return $this->initialServerRequest;
    }

    /**
     * @return ServerRequestFactoryInterface
     */
    protected function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        if (!$this->serverRequestFactory instanceof ServerRequestFactoryInterface) {
            $this->serverRequestFactory = new ServerRequestFactory();
        }

        return $this->serverRequestFactory;
    }
    
    /**
     * @return UserRoleConfiguration
     */
    protected function getUserRoleConfiguration(): UserRoleConfiguration
    {
        if (!$this->userRoleConfiguration instanceof UserRoleConfiguration) {
            $this->userRoleConfiguration = new UserRoleConfiguration();
        }

        return $this->userRoleConfiguration;
    }
    
    /**
     * @return ReflectionClass
     */
    protected function getUserRoleConfigurationReflection(): ReflectionClass
    {
        if (!$this->userRoleConfigurationReflection instanceof ReflectionClass) {
            $this->userRoleConfigurationReflection = new ReflectionClass(UserRoleConfiguration::class);
        }

        return $this->userRoleConfigurationReflection;
    }

    /**
     * @return ReflectionClass
     */
    protected function getAuthorizeMiddlewareReflection(): ReflectionClass
    {
        if (!$this->authorizeMiddlewareReflection instanceof ReflectionClass) {
            $this->authorizeMiddlewareReflection = new ReflectionClass(AuthorizeMiddleware::class);
        }

        return $this->authorizeMiddlewareReflection;
    }

    /**
     * @return callable
     */
    protected function getSlimMiddlewareCallable(): callable
    {
        $callable = function (
            ServerRequestInterface $request,
            ResponseInterface $response
        ): ResponseInterface {
            return $response;
        };

        return $callable;
    }

    /**
     * @return ResponseInterface
     */
    protected function getInitialResponse(): ResponseInterface
    {
        if (!$this->initialResponse instanceof ResponseInterface) {
            $this->initialResponse = $this->getResponseFactory()->createResponse();
        }

        return $this->initialResponse;
    }

    /**
     * @return ResponseFactoryInterface
     */
    protected function getResponseFactory(): ResponseFactoryInterface
    {
        if (!$this->responseFactory instanceof ResponseFactoryInterface) {
            $this->responseFactory = new ResponseFactory();
        }

        return $this->responseFactory;
    }

    /**
     * @return RequestHandlerInterface
     */
    protected function getRequestHandler(): RequestHandlerInterface
    {
        if (!$this->requestHandler instanceof RequestHandlerInterface) {
            $this->requestHandler = new RequestHandler();
        }

        return $this->requestHandler;
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     */
    protected function isResponseSuccess(ResponseInterface $response): bool
    {
        return 200 === $response->getStatusCode();
    }
    
    /**
     * @param ResponseInterface $response
     * @return bool
     */
    protected function isResponseForbidden(ResponseInterface $response): bool
    {
        return 403 === $response->getStatusCode();
    }
}
