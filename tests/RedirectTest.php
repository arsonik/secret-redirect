<?php
use PHPUnit\Framework\TestCase;

use SecretRedirect\SecretRedirect;

final class RedirectTest extends TestCase
{
    public function testGoogleSecure()
    {
        $secret = new SecretRedirect();
        $secret->mode = SecretRedirect::MODE_RETURN;
        $secret->forwardCookies = false;
        $url = $secret->redirect('http://google.fr');
        $this->assertEquals('http://www.google.fr/', $url);
    }
}
