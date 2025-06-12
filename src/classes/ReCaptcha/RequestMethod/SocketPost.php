<?php

namespace ReCaptcha\RequestMethod;

use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;

/**
 * Sends a POST request to the reCAPTCHA service, but makes use of fsockopen()
 * instead of get_file_contents(). This is to account for people who may be on
 * servers where allow_url_open is disabled.
 */
class SocketPost implements RequestMethod {

    /**
     * Socket to the reCAPTCHA service
     * @var Socket
     */
    private $socket;

    /**
     * Only needed if you want to override the defaults
     *
     * @param \ReCaptcha\RequestMethod\Socket $socket optional socket, injectable for testing
     * @param string $siteVerifyUrl URL for reCAPTCHA siteverify API
     */
    public function __construct(Socket $socket = null, $siteVerifyUrl = null) {

        $this->socket = (is_null($socket)) ? new Socket() : $socket;
        $this->siteVerifyUrl = (is_null($siteVerifyUrl)) ? ReCaptcha::SITE_VERIFY_URL : $siteVerifyUrl;
    }

    /**
     * Submit the POST request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params) {

        $errno = 0;
        $errstr = '';
        $urlParsed = parse_url($this->siteVerifyUrl);

        if (false === $this->socket->fsockopen('ssl://' . $urlParsed['host'], 443, $errno, $errstr, 30)) {
            return '{"success": false, "error-codes": ["' . ReCaptcha::E_CONNECTION_FAILED . '"]}';
        }

        $content = $params->toQueryString();

        $request = "POST " . $urlParsed['path'] . " HTTP/1.0\r\n";
        $request .= "Host: " . $urlParsed['host'] . "\r\n";
        $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $request .= "Content-length: " . strlen($content) . "\r\n";
        $request .= "Connection: close\r\n\r\n";
        $request .= $content . "\r\n\r\n";

        $this->socket->fwrite($request);
        $response = '';

        while (!$this->socket->feof()) {
            $response .= $this->socket->fgets(4096);
        }

        $this->socket->fclose();

        if (0 !== strpos($response, 'HTTP/1.0 200 OK')) {
            return '{"success": false, "error-codes": ["' . ReCaptcha::E_BAD_RESPONSE . '"]}';
        }

        $parts = preg_split("#\n\s*\n#Uis", $response);

        return $parts[1];
    }

}
