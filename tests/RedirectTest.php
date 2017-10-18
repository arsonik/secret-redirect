<?php
use PHPUnit\Framework\TestCase;

use SecretRedirect\SecretRedirect;

final class RedirectTest extends TestCase
{
    public function testGoogleLocation()
    {
        $secret = new SecretRedirect();
        $secret->forwardCookies = false;
        // Request non www
        $url = $secret->location('http://google.fr');
        // Make sure we are redirected to www
        $this->assertEquals('http://www.google.fr/', $url, 'Cannot get redirected url');
    }

    /**
     * @runInSeparateProcess
     */
    public function testGoogleCookies()
    {
        $secret = new SecretRedirect();
        $secret->forwardCookies = true;
        $result = $secret->request('https://google.fr', ['http' => ['follow_location' => 1]]);
        $this->assertTrue(is_array($result['cookies']), 'No cookie returned');
        $found = false;
        foreach ($result['cookies'] as $cookie) {
            if (strpos($cookie, 'NID=') > 0) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'Cookie NID not found');
    }

    /**
     * @runInSeparateProcess
     */
    public function testGoogleRedirect()
    {
        $secret = new SecretRedirect();
        $secret->forwardCookies = false;
        $this->assertTrue($secret->redirect('http://google.fr'), 'Was not redirected');
    }

    public function testGoogleContent()
    {
        $secret = new SecretRedirect();
        $secret->forwardCookies = false;
        $content = $secret->content('http://google.fr');
        $this->assertGreaterThan(0, preg_match('!<title>Google</title>!', $content), 'Cannot find <title>');
    }
}
