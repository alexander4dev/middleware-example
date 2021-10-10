## Installation
```sh
composer require arus/api-authorization:^0.0
```

## Usage example
```php
$rbacConfig = [
	'routeName1' => [
    	'roleName1',
        ...
        'roleNameN',
    ],
    ...
	'routeNameN' => [
    	'roleName1',
        ...
        'roleNameN',
    ],    
];

$authorizationMiddleware = new \Arus\Authorization\AuthorizeMiddleware($rbacConfig);
```

## Allowed roles
   From parent to child:
   - **grand** 🠞 **administrator** 🠞 **redactor** 🠞 **user**
   
   - **local**
