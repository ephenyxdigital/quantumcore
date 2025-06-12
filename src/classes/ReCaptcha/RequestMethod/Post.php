<?php

namespace ReCaptcha\RequestMethod;

use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use ReCaptcha\RequestParameters;

/**
 * Sends POST requests to the reCAPTCHA service.
 */
class Post implements RequestMethod {

    /**
     * URL for reCAPTCHA siteverify API
     * @var string
     */
    private $siteVerifyUrl;

    /**
     * Only needed if you want to override the defaults
     *
     * @param string $siteVerifyUrl URL for reCAPTCHA siteverify API
     */
    public function __construct($siteVerifyUrl = null) {

        $this->siteVerifyUrl = (is_null($siteVerifyUrl)) ? ReCaptcha::SITE_VERIFY_URL : $siteVerifyUrl;
    }

    /**
     * Submit the POST request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params) {

        $options = [
            'http' => [
                'header'      => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'      => 'POST',
                'content'     => $params->toQueryString(),
                // Force the peer to validate (not needed in 5.6.0+, but still works)
                'verify_peer' => true,
            ],
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($this->siteVerifyUrl, false, $context);

        if ($response !== false) {
            return $response;
        }

        return '{"success": false, "error-codes": ["' . ReCaptcha::E_CONNECTION_FAILED . '"]}';
    }

}
