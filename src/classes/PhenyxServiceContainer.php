<?php

class PhenyxServiceContainer {

    private static $instance = null;

    private $app = null;

    private $services = [];

    private $constructors = [];

    // Constructor

    private function __construct() {

        self::$instance = $this;
    }

    public static function getInstance() {

        if (!isset(static::$instance)) {
            static::$instance = new PhenyxServiceContainer();
        }

        return static::$instance;
    }

    // Methods

    public function setApp($app) {

        $this->app = $app;
    }

    public function getApp() {
        return $this->app;
    }

    public function get($id) {
        // Create the service if not already availabe

        if (!isset($this->services[$id]) && isset($this->constructors[$id])) {

            if ($this->constructors[$id][1]) {
                $this->services[$id] = new $this->constructors[$id][0]($this->constructors[$id][1]);
            } else {
                $this->services[$id] = new $this->constructors[$id][0];
            }

            $this->services[$id]->setServiceContainer($this);
        }

        // Return the service

        return $this->services[$id];
    }

    public function registerService($id, $ServiceClass, $opts = []) {
        // Store the service constructor

        $this->constructors[$id] = [$ServiceClass, $opts];
    }

    public function registerServiceInstance($id, $instance) {
        // Store the service instance

        $this->services[$id] = $instance;
    }

    public function clean() {
        // Remove the registered services

        foreach ($this->services as $service) {
            $service->onRemove();
        }

        $this->services = [];
    }

}

?>
