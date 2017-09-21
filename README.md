# secret-redirect


### Usage
```php
$secret = new SecretRedirect();
$secret->cookiePrefix = 'm2sa_';
$secret->redirect('http://ads-server.tld/campaign?id=xxxx', 'http://fallback.tld/azz');
```