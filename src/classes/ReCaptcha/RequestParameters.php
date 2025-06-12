<?php

namespace ReCaptcha;

/**
 * Stores and formats the parameters for the request to the reCAPTCHA service.
 */
class RequestParameters {

    /**
     * The shared key between your site and reCAPTCHA.
     * @var string
     */
    private $secret;

    /**
     * The user response token provided by reCAPTCHA, verifying the user on your site.
     * @var string
     */
    private $response;

    /**
     * Remote user's IP address.
     * @var string
     */
    private $remoteIp;

    /**
     * Client version.
     * @var string
     */
    private $version;

    /**
     * Initialise parameters.
     *
     * @param string $secret Site secret.
     * @param string $response Value from g-captcha-response form field.
     * @param string $remoteIp User's IP address.
     * @param string $version Version of this client library.
     */
    public function __construct($secret, $response, $remoteIp = null, $version = null) {

        $this->secret = $secret;
        $this->response = $response;
        $this->remoteIp = $remoteIp;
        $this->version = $version;
    }

    /**
     * Array representation.
     *
     * @return array Array formatted parameters.
     */
    public function toArray() {

        $params = ['secret' => $this->secret, 'response' => $this->response];

        if (!is_null($this->remoteIp)) {
            $params['remoteip'] = $this->remoteIp;
        }

        if (!is_null($this->version)) {
            $params['version'] = $this->version;
        }

        return $params;
    }

    /**
     * Query string representation for HTTP request.
     *
     * @return string Query string formatted parameters.
     */
    public function toQueryString() {

        return http_build_query($this->toArray(), '', '&');
    }

}
