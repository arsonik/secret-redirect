# secret-redirect
[![Latest Stable Version](https://img.shields.io/packagist/v/arsonik/secret-redirect.svg)](https://packagist.org/packages/arsonik/secret-redirect)

A discrete way of forwarding client request.

## Installation
```sh
composer require arsonik/secret-redirect
```
```php
require './vendor/autoload.php';
```

## Usage
### Forward client with the Location header (302 status)
```php
$secret = new SecretRedirect();
$secret->cookiePrefix = 'm2sa_';
$secret->redirect('http://ads-server.tld/campaign?id=xxxx', 'http://fallback.tld/azz');
exit;
```

### Returns Location header url
```php
$secret = new SecretRedirect();
$secret->cookiePrefix = 'm2sa_';
$url = $secret->location('http://ads-server.tld/campaign?id=xxxx', 'http://fallback.tld/azz');
```

### Returns destination page content
```php
$secret = new SecretRedirect();
$secret->cookiePrefix = 'm2sa_';
$content = $secret->content('http://ads-server.tld/campaign?id=xxxx');
```


## Optional configuration
### `SecretRedirect` class parameters
- `cookiePrefix` String 
- `forwardCookies` Boolean
- `serverUsesXHttpForwardedFor` Boolean 
- `timeout` Float 