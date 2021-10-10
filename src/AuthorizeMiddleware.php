<?php

declare(strict_types=1);

namespace Arus\Authorization;

use Arus\ApiFoundation\Http\Responder;
use Arus\Authorization\Exception\ApiTokenParamsException;
use Arus\Authorization\Exception\ConfigValidationException;
use Arus\Authorization\Exception\RuntimeException;
use Arus\Authorization\User;
use Arus\Authorization\UserRoleConfiguration;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sunrise\Http\Factory\ResponseFactory;
use Zend\Permissions\Rbac\Exception\ExceptionInterface as RbacException;
use Zend\Permissions\Rbac\Rbac;
use Zend\Permissions\Rbac\Role;

use function array_diff;
use function array_keys;
use function get_class;
use function implode;
use function is_array;
use function is_object;
use function method_exists;
use function sprintf;

class AuthorizeMiddleware implements MiddlewareInterface
{
    private const REQUEST_ATTRIBUTE_ROUTE_OBJECT = 'route';
    private const REQUEST_ATTRIBUTE_ROUTE_OBJECT_METHOD_GET_NAME = 'getName';
    private const REQUEST_ATTRIBUTE_ROUTE_NAME = '@route';
    private const REQUEST_ATTRIBUTE_USER = '@user';

    private const EXCEPTION_RBAC = 'RbacException: %s';
    private const EXCEPTION_ROUTE_METHOD = 'Route object(%s) doesn\'t have method "%s"';
    private const EXCEPTION_API_TOKEN_PARAMS = 'ApiTokenParamsException: %s';
    private const EXCEPTION_CONFIG_VALIDATION = 'This role(s) is not allowed: %s';

    /**
     * @var array
     */
    private $rbacConfig = [];

    /**
     * @var Rbac|null
     */
    private $rbac;

    /**
     * @var Responder|null
     */
    private $responder;

    /**
     * @var ResponseFactoryInterface|null
     */
    private $responseFactory;

    /**
     * @var User|null
     */
    private $user;

    /**
     * @param array $rbacConfig
     */
    public function __construct(array $rbacConfig = [])
    {
        $this->setRbacConfig($rbacConfig);
    }

    /**
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface      $response
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $continue
    ): ResponseInterface {
        if (!$this->isGrantedRequest($request)) {
            return $this->createForbiddenResponse();
        }

        $processedRequest = $this->postProcessRequest($request);

        return $continue($processedRequest, $response);
    }

    /**
     * @param  ServerRequestInterface  $request
     * @param  RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isGrantedRequest($request)) {
            return $this->createForbiddenResponse();
        }

        $processedRequest = $this->postProcessRequest($request);

        return $handler->handle($processedRequest);
    }

    /**
     * @param  string $role
     * @param  string $permission
     * @return bool
     * @throws RuntimeException
     */
    public function isGranted(string $role, string $permission): bool
    {
        try {
            return $this->getRbac()->isGranted($role, $permission);
        } catch (RbacException $e) {
            throw new RuntimeException(sprintf(self::EXCEPTION_RBAC, $e->getMessage()));
        }
    }

    /**
     * @param  ServerRequestInterface $request
     * @return bool
     */
    public function isGrantedRequest(ServerRequestInterface $request): bool
    {
        $routeName = $this->getRouteNameFromRequest($request);
        $roleName = $this->getRequestUser($request)->getGroup();

        return $this->isGranted($roleName, $routeName);
    }

    /**
     * @param  array $rbacConfig
     * @return AuthorizeMiddleware
     */
    public function setRbacConfig(array $rbacConfig): self
    {
        $this->rbacConfig = $this->parseRbacConfig($rbacConfig);

        return $this;
    }

    /**
     * @param  ServerRequestInterface $request
     * @return string
     * @throws RuntimeException
     */
    protected function getRouteNameFromRequest(ServerRequestInterface $request): string
    {
        $routeObject = $request->getAttribute(self::REQUEST_ATTRIBUTE_ROUTE_OBJECT);

        if (is_object($routeObject)) {
            $routeNameGetter = self::REQUEST_ATTRIBUTE_ROUTE_OBJECT_METHOD_GET_NAME;

            if (!method_exists($routeObject, $routeNameGetter)) {
                throw new RuntimeException(
                    sprintf(self::EXCEPTION_ROUTE_METHOD, get_class($routeObject), $routeNameGetter)
                );
            }

            $routeName = $routeObject->$routeNameGetter();
        } else {
            $routeName = $request->getAttribute(self::REQUEST_ATTRIBUTE_ROUTE_NAME);
        }

        return $routeName;
    }

    /**
     * @param  ServerRequestInterface $request
     * @return User
     * @throws RuntimeException
     */
    protected function getRequestUser(ServerRequestInterface $request): User
    {
        if (!$this->user instanceof User) {
            try {
                $apiTokenParams = ApiTokenParams::createFromRequest($request);

                $this->user = new User(
                    $apiTokenParams->getUserId(),
                    $apiTokenParams->getUserGroup(),
                    $apiTokenParams->getUserFullName()
                );
            } catch (ApiTokenParamsException $e) {
                throw new RuntimeException(sprintf(self::EXCEPTION_API_TOKEN_PARAMS, $e->getMessage()));
            }
        }

        return $this->user;
    }

    /**
     * @return ResponseInterface
     */
    protected function createForbiddenResponse(): ResponseInterface
    {
        $response = $this->getResponseFactory()->createResponse();
        $forbiddenResponse = $this->getResponder()->forbidden($response);

        return $forbiddenResponse;
    }

    /**
     * @return Responder
     */
    protected function getResponder(): Responder
    {
        if (!$this->responder instanceof Responder) {
            $this->responder = new Responder();
        }

        return $this->responder;
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
     * @return Rbac
     */
    protected function getRbac(): Rbac
    {
        if (!$this->rbac instanceof Rbac) {
            $this->rbac = new Rbac();
            $this->rbac->setCreateMissingRoles(true);

            foreach (UserRoleConfiguration::ALLOWED_ROLES as $roleName) {
                $role = $this->rbac->hasRole($roleName)
                        ? $this->rbac->getRole($roleName)
                        : new Role($roleName);

                $rolePermissions = $this->rbacConfig[$roleName] ?? [];

                foreach ($rolePermissions as $permissionName) {
                    $role->addPermission($permissionName);
                }

                $this->rbac->addRole($role, UserRoleConfiguration::ROLES_PARENTS[$roleName] ?? []);
            }
        }

        return $this->rbac;
    }

    /**
     * @param  array $rbacConfig
     * @return array
     * @throws ConfigValidationException
     */
    protected function parseRbacConfig(array $rbacConfig): array
    {
        $rolesPermissions = [];

        foreach ($rbacConfig as $routeName => $allowedRoles) {
            if (!is_array($allowedRoles)) {
                $allowedRoles = [$allowedRoles];
            }

            foreach ($allowedRoles as $allowedRoleName) {
                $rolesPermissions[$allowedRoleName][] = $routeName;
            }
        }

        $configRoles = array_keys($rolesPermissions);
        $notAllowedRoles = array_diff($configRoles, UserRoleConfiguration::ALLOWED_ROLES);

        if ($notAllowedRoles) {
            throw new ConfigValidationException(
                sprintf(self::EXCEPTION_CONFIG_VALIDATION, implode(', ', $notAllowedRoles))
            );
        }

        return $rolesPermissions;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function postProcessRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $user = $this->getRequestUser($request);
        $requestWithUser = $request->withAttribute(self::REQUEST_ATTRIBUTE_USER, $user);

        return $requestWithUser;
    }
}
