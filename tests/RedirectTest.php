<?php
use PHPUnit\Framework\TestCase;

final class RedirectTest extends TestCase
{
    public function testGoogleSecure()
    {
        $secret = new \SecretRedirect\SecretRedirect();
        $secret->mode = \SecretRedirect\SecretRedirect::MODE_RETURN;
        $secret->forwardCookies = false;
        $url = $secret->redirect('http://google.fr');
        $this->assertEquals('http://www.google.fr/', $url);
    }
}
