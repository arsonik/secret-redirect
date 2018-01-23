<?php
namespace SecretRedirect;

/**
 * Class SecretRedirect
 *
 * @author Florian Morello <arsonik@me.com>
 */
class SecretRedirect {
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
     * @return string|null forwarded ip
     */
    public function forwardedClientIp() {
        $forward = null;
        if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            if (isset($requestHeaders['X-Forwarded-For'])) {
                $forward = $requestHeaders['X-Forwarded-For'];
            }
        } else if ($this->vserver('HTTP_X_FORWARDED_FOR')) {
            $forward = $this->vserver('HTTP_X_FORWARDED_FOR');
        }
        if ($forward) {
            foreach (preg_split('/, ?/', $forward) as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return null;
    }

    /**
     * Raw request
     * avoid using it
     *
     * @param $url
     * @param $context array Override stream context param
     * @return array|bool
     */
    function request($url, array $context = []) {
        $clientIp = $this->vserver('REMOTE_ADDR');

        if ($this->serverUsesXHttpForwardedFor) {
            $clientIp = $this->forwardedClientIp() ?: $clientIp;
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

        $context = stream_context_create(array_merge([
                'http' => [
                    'timeout' => $this->timeout,
                    'follow_location' => 0,
                    'header' => implode("\n", $headers),
                    'ignore_errors' => true,
                ]
            ], $context));

        $result = [
            'data' => null,
            'metas' => null,
        ];
        $stream = @fopen($url, "r", false, $context);
        if ($stream) {
            $result['metas'] = stream_get_meta_data($stream);
            $result['data'] = stream_get_contents($stream);
            fclose($stream);

            if ($this->forwardCookies) {
                $result['cookies'] = [];
                // Loop through headers
                foreach ($this->extractHeaders($result['metas'], 'Set-Cookie') as $header) {
                    // remove domain specific cookie
                    $header = preg_replace('/[Dd]omain=[^;]+(;\s*|$)/', '', $header);
                    // remove Secure, HttpOnly attributes
                    $header = preg_replace('/(Secure|HttpOnly)(;\s*|$)/', '', $header);
                    $result['cookies'][] = $header;

                    header(str_replace('Set-Cookie: ', 'Set-Cookie: '. $this->cookiePrefix, $header), false);
                }
            }
        }

        return $result;
    }

    /**
     * Extract header from stream_get_meta_data response
     *
     * @param $metas
     * @param $name
     * @return array
     */
    private function extractHeaders($metas, $name) {
        if (!is_array($metas) || !isset($metas['wrapper_data'])) {
            return [];
        }

        return array_filter($metas['wrapper_data'], function($header) use ($name) {
            return strpos($header, $name . ': ') === 0;
        });
    }

    /**
     * @param $url string Url that will be requested passing client info, MUST return a "Location: xxx" header
     * @param $fallbackUrl string Define an url to redirect the traffic to if the url is unresponsive
     * @return bool
     */
    public function redirect($url, $fallbackUrl = null) {
        $result = $this->request($url);

        $locations = $this->extractHeaders($result['metas'], 'Location');
        if ($locations) {
            $redirect = str_replace('Location: ', '', current($locations));
        } else {
            $redirect = $fallbackUrl;
        }

        if ($redirect) {
            header($redirect, true, 302);
            return true;
        }

        return false;
    }


    /**
     * Return header Location value
     *
     * @param $url
     * @return mixed
     */
    public function location($url, $fallbackUrl = null) {
        $result = $this->request($url);

        $locations = $this->extractHeaders($result['metas'], 'Location');
        if ($locations) {
            $redirect = str_replace('Location: ', '', current($locations));
        } else {
            $redirect = $fallbackUrl;
        }

        return $redirect;
    }

    /**
     * Retrieve content
     *
     * @param $url
     * @return mixed
     */
    public function content($url) {
        $result = $this->request($url, ['http' => ['follow_location' => 1]]);
        return $result['data'];
    }
}
