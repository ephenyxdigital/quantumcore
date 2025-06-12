<?php

namespace ReCaptcha;

/**
 * reCAPTCHA client.
 */
class ReCaptcha {

    /**
     * Version of this client library.
     * @const string
     */
    const VERSION = 'php_1.2.4';

    /**
     * URL for reCAPTCHA siteverify API
     * @const string
     */
    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Invalid JSON received
     * @const string
     */
    const E_INVALID_JSON = 'invalid-json';

    /**
     * Could not connect to service
     * @const string
     */
    const E_CONNECTION_FAILED = 'connection-failed';

    /**
     * Did not receive a 200 from the service
     * @const string
     */
    const E_BAD_RESPONSE = 'bad-response';

    /**
     * Not a success, but no error codes received!
     * @const string
     */
    const E_UNKNOWN_ERROR = 'unknown-error';

    /**
     * ReCAPTCHA response not provided
     * @const string
     */
    const E_MISSING_INPUT_RESPONSE = 'missing-input-response';

    /**
     * Expected hostname did not match
     * @const string
     */
    const E_HOSTNAME_MISMATCH = 'hostname-mismatch';

    /**
     * Expected APK package name did not match
     * @const string
     */
    const E_APK_PACKAGE_NAME_MISMATCH = 'apk_package_name-mismatch';

    /**
     * Expected action did not match
     * @const string
     */
    const E_ACTION_MISMATCH = 'action-mismatch';

    /**
     * Score threshold not met
     * @const string
     */
    const E_SCORE_THRESHOLD_NOT_MET = 'score-threshold-not-met';

    /**
     * Challenge timeout
     * @const string
     */
    const E_CHALLENGE_TIMEOUT = 'challenge-timeout';

    /**
     * Shared secret for the site.
     * @var string
     */
    private $secret;

    /**
     * Method used to communicate with service. Defaults to POST request.
     * @var RequestMethod
     */
    private $requestMethod;

    /**
     * Create a configured instance to use the reCAPTCHA service.
     *
     * @param string $secret The shared key between your site and reCAPTCHA.
     * @param RequestMethod $requestMethod method used to send the request. Defaults to POST.
     * @throws \RuntimeException if $secret is invalid
     */
    public function __construct($secret, RequestMethod $requestMethod = null) {

        if (empty($secret)) {
            throw new \RuntimeException('No secret provided');
        }

        if (!is_string($secret)) {
            throw new \RuntimeException('The provided secret must be a string');
        }

        $this->secret = $secret;
        $this->requestMethod = (is_null($requestMethod)) ? new RequestMethod\Post() : $requestMethod;
    }

    /**
     * Calls the reCAPTCHA siteverify API to verify whether the user passes
     * CAPTCHA test and additionally runs any specified additional checks
     *
     * @param string $response The user response token provided by reCAPTCHA, verifying the user on your site.
     * @param string $remoteIp The end user's IP address.
     * @return Response Response from the service.
     */
    public function verify($response, $remoteIp = null) {

        // Discard empty solution submissions

        if (empty($response)) {
            $recaptchaResponse = new Response(false, [self::E_MISSING_INPUT_RESPONSE]);
            return $recaptchaResponse;
        }

        $params = new RequestParameters($this->secret, $response, $remoteIp, self::VERSION);
        $rawResponse = $this->requestMethod->submit($params);
        $initialResponse = Response::fromJson($rawResponse);
        $validationErrors = [];

        if (isset($this->hostname) && strcasecmp($this->hostname, $initialResponse->getHostname()) !== 0) {
            $validationErrors[] = self::E_HOSTNAME_MISMATCH;
        }

        if (isset($this->apkPackageName) && strcasecmp($this->apkPackageName, $initialResponse->getApkPackageName()) !== 0) {
            $validationErrors[] = self::E_APK_PACKAGE_NAME_MISMATCH;
        }

        if (isset($this->action) && strcasecmp($this->action, $initialResponse->getAction()) !== 0) {
            $validationErrors[] = self::E_ACTION_MISMATCH;
        }

        if (isset($this->threshold) && $this->threshold > $initialResponse->getScore()) {
            $validationErrors[] = self::E_SCORE_THRESHOLD_NOT_MET;
        }

        if (isset($this->timeoutSeconds)) {
            $challengeTs = strtotime($initialResponse->getChallengeTs());

            if ($challengeTs > 0 && time() - $challengeTs > $this->timeoutSeconds) {
                $validationErrors[] = self::E_CHALLENGE_TIMEOUT;
            }

        }

        if (empty($validationErrors)) {
            return $initialResponse;
        }

        return new Response(
            false,
            array_merge($initialResponse->getErrorCodes(), $validationErrors),
            $initialResponse->getHostname(),
            $initialResponse->getChallengeTs(),
            $initialResponse->getApkPackageName(),
            $initialResponse->getScore(),
            $initialResponse->getAction()
        );
    }

    /**
     * Provide a hostname to match against in verify()
     * This should be without a protocol or trailing slash, e.g. www.google.com
     *
     * @param string $hostname Expected hostname
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setExpectedHostname($hostname) {

        $this->hostname = $hostname;
        return $this;
    }

    /**
     * Provide an APK package name to match against in verify()
     *
     * @param string $apkPackageName Expected APK package name
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setExpectedApkPackageName($apkPackageName) {

        $this->apkPackageName = $apkPackageName;
        return $this;
    }

    /**
     * Provide an action to match against in verify()
     * This should be set per page.
     *
     * @param string $action Expected action
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setExpectedAction($action) {

        $this->action = $action;
        return $this;
    }

    /**
     * Provide a threshold to meet or exceed in verify()
     * Threshold should be a float between 0 and 1 which will be tested as response >= threshold.
     *
     * @param float $threshold Expected threshold
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setScoreThreshold($threshold) {

        $this->threshold = floatval($threshold);
        return $this;
    }

    /**
     * Provide a timeout in seconds to test against the challenge timestamp in verify()
     *
     * @param int $timeoutSeconds Expected hostname
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setChallengeTimeout($timeoutSeconds) {

        $this->timeoutSeconds = $timeoutSeconds;
        return $this;
    }

}
