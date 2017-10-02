<?php
namespace SecretRedirect;

/**
 * Class SecretRedirect
 *
 * @author Florian Morello <arsonik@me.com>
 */
class SecretRedirect {
    const MODE_RETURN = 1;
    const MODE_REDIRECT = 2;

    /**
     * Define cookies prefix
     * 2-way forwarding
     * @var string
     */
    public $cookiePrefix = 'd2c_';

    /**
     * Define if it should use
     * HTTP_X_FORWARDED_FOR i/o REMOTE_ADDR
     * _SERVER variable
     * to identify client ip
     * @var bool
     */
    public $serverUsesXHttpForwardedFor = true;

    /**
     * Define if the class should redirect or return the location
     * @var int
     */
    public $mode = SecretRedirect::MODE_REDIRECT;

    /**
     * Allowed time for the request to respond
     * @var float
     */
    public $timeout = 5.0;

    /**
     * @var bool
     */
    public $forwardCookies = true;

    private function vserver($name) {
        return isset($_SERVER[$name]) ? $_SERVER[$name] : null;
    }

    /**
     * @param $url string Url that will be requested passing client info, MUST return a "Location: xxx" header
     * @param $fallbackUrl string Define an url to redirect the traffic to if the url is unresponsive
     * @return string|bool
     */
    public function redirect($url, $fallbackUrl = null) {
        if ($this->serverUsesXHttpForwardedFor && $this->vserver('HTTP_X_FORWARDED_FOR')) {
            $clientIp = $this->vserver('HTTP_X_FORWARDED_FOR');
        } else {
            $clientIp = $this->vserver('REMOTE_ADDR');
        }

        $cookies = [];

        if ($this->forwardCookies) {
            $cookies = array_filter($_COOKIE, function ($k) {
                return strpos($k, $this->cookiePrefix) === 0;
            }, ARRAY_FILTER_USE_KEY);
            array_walk($cookies, function(&$v, $k) {
                $v = "$k=". str_replace($this->cookiePrefix, '', $v);
            });
        }

        $headers = array_filter([
            'User-Agent' => $this->vserver('HTTP_USER_AGENT'),
            'Accept'     => $this->vserver('HTTP_ACCEPT'),
            'Accept-Language' => $this->vserver('HTTP_ACCEPT_LANGUAGE'),
            'X-Forwarded-For' => $clientIp,
            'Referer' => $this->vserver('HTTP_REFERER'),
            'Cookie' => implode(";", $cookies)
        ]);

        array_walk($headers, function(&$v, $k) { $v = "$k: $v"; });

        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'follow_location' => 0,
                'header' => implode("\n", $headers),
                'ignore_errors' => true,
            ]
        ]);

        $result = null;
        $stream = @fopen($url, "r", false, $context);
        if ($stream) {
            $metas = stream_get_meta_data($stream);
            fclose($stream);

            $sc = 'Set-Cookie: ';
            foreach ($metas['wrapper_data'] as &$header) {
                if ($this->forwardCookies && strpos($header, $sc) === 0) {
                    header(str_replace($sc, $sc . $this->cookiePrefix, $header), false);
                } elseif (strpos($header, 'Location: ') === 0) {
                    $result = $header;
                }
            }
        }

        if (is_null($result)) {
            if ($this->mode === SecretRedirect::MODE_REDIRECT && $fallbackUrl) {
                $result = 'Location: ' . $fallbackUrl;
            } else {
                return false;
            }
        }

        if ($this->mode === SecretRedirect::MODE_REDIRECT) {
            header($result, true, 302);
            return true;
        } else {
            return str_replace('Location: ', '', $result);
        }
    }
}
