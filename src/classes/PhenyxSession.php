<?php

class PhenyxSession extends PhenyxServices {

    const SESSION_NAMESPACE = 'phenyx_digital';

    protected static $instance;

    public $session_namspace;

    public function __construct($session_namspace = null) {

        if (!is_null($session_namspace)) {

            $this->session_namspace = $session_namspace;
        } else {

            $this->session_namspace = self::SESSION_NAMESPACE;
        }

        if (isset($_SERVER['HTTP_X_SID'])) {

            session_id($_SERVER['HTTP_X_SID']);

        } else

        if (isset($_GET['_sid'])) {

            session_id($_GET['_sid']);
        }

        session_set_cookie_params(0);

        @session_start();

        if (empty($_SESSION[self::SESSION_NAMESPACE])) {
            $_SESSION[self::SESSION_NAMESPACE] = [];
        }

    }

    public static function getInstance() {

        if (!isset(static::$instance)) {
            static::$instance = new PhenyxSession();
        }

        return static::$instance;
    }

    public function get($key) {

        return isset($_SESSION[self::SESSION_NAMESPACE][$key]) ? $_SESSION[self::SESSION_NAMESPACE][$key] : null;
    }

    public function set($key, $value) {

        $_SESSION[self::SESSION_NAMESPACE][$key] = $value;
    }

    public function remove($key) {

        unset($_SESSION[self::SESSION_NAMESPACE][$key]);
    }

    public function removeStartingKey($key) {

        foreach ($_SESSION[self::SESSION_NAMESPACE] as $k => $value) {

            if (str_starts_with($k, $key)) {
                unset($_SESSION[self::SESSION_NAMESPACE][$k]);
            }

        }

    }

    public function removeEndByKey($key) {

        foreach ($_SESSION[self::SESSION_NAMESPACE] as $k => $value) {

            if (str_ends_with($k, $key)) {
                unset($_SESSION[self::SESSION_NAMESPACE][$k]);
            }

        }

    }

    public function getSessionValues() {
        ini_set('memory_limit', '-1');
        $result = [];

        foreach ($_SESSION[self::SESSION_NAMESPACE] as $key => $value) {

            if (is_array($value)) {
                $result[$key] = $value;
            } else

            if (Validate::isJSON($value)) {
                $result[$key] = Tools::jsonDecode($value, true);
            } else {
                $result[$key] = $value;
            }

        }

        ksort($result);
        return $result;
    }

    public function destroy() {

        session_destroy();
    }

    public function getId() {

        return session_id();
    }

}

?>
