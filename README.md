# secret-redirect


### Installation
```sh
composer require arsonik/secret-redirect
```
```php
require './vendor/autoload.php';
```

### Usage
```php
$secret = new SecretRedirect();
$secret->cookiePrefix = 'm2sa_';
$secret->redirect('http://ads-server.tld/campaign?id=xxxx', 'http://fallback.tld/azz');
exit;
```