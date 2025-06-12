<?php

// Base class for all services implementing the Singleton pattern

class PhenyxServices extends stdClass {
    // Fields

    protected $services;

    public $context;

    public function __construct() {

        $this->context = Context::getContext();

        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();

        }

        if (!isset($this->context->_tools)) {
            $this->context->_tools = PhenyxTool::getInstance();
        }

        if (!isset($this->context->language)) {
            $this->context->language = $this->context->_tools->jsonDecode($this->context->_tools->jsonEncode(Language::buildObject($this->context->phenyxConfig->get('EPH_LANG_DEFAULT'))));
        }

    }

    // Methods

    public function setServiceContainer($container) {

        $this->services = $container;

        $this->onRegister();
    }

    public function onRegister() {}

    public function onRemove() {}

    public function get($serviceId) {

        return $this->services->get($serviceId);
    }

    public function getApp() {

        return $this->services->getApp();
    }

}

?>
