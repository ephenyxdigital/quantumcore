<?php

namespace ReCaptcha;

/**
 * Method used to send the request to the service.
 */
interface RequestMethod {

    /**
     * Submit the request with the specified parameters.
     *
     * @param RequestParameters $params Request parameters
     * @return string Body of the reCAPTCHA response
     */
    public function submit(RequestParameters $params);
}
