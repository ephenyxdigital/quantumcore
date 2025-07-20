<?php

/**
 * Class HelperCalendarCore
 *
 * @since 1.8.1.0
 */
class HelperCalendar extends Helper {

    const DEFAULT_DATE_FORMAT = 'dd/mm/Y';
    const DEFAULT_COMPARE_OPTION = 1;

    // @codingStandardsIgnoreStart
    private $_actions;
    private $_compare_actions;
    private $_compare_date_from;
    private $_compare_date_to;
    private $_compare_date_option;
    private $_date_format;
    private $_date_from;
    private $_date_to;
    private $_rtl;
    // @codingStandardsIgnoreEnd

    /**
     * HelperCalendarCore constructor.
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function __construct() {

        $this->base_folder = 'helpers/calendar/';
        $this->base_tpl = 'calendar.tpl';
        parent::__construct();
    }

    /**
     * @param Traversable[] $value
     *
     * @return $this
     * @throws PhenyxException
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setActions($value) {

        if (!is_array($value) && !$value instanceof Traversable) {
            throw new PhenyxException('Actions value must be an traversable array');
        }

        $this->_actions = $value;

        return $this;
    }

    /**
     * @return array
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getActions() {

        if (!isset($this->_actions)) {
            $this->_actions = [];
        }

        return $this->_actions;
    }

    /**
     * @param Traversable[] $value
     *
     * @return $this
     * @throws PhenyxException
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setCompareActions($value) {

        if (!is_array($value) && !$value instanceof Traversable) {
            throw new PhenyxException('Actions value must be an traversable array');
        }

        $this->_compare_actions = $value;

        return $this;
    }

    /**
     * @return array
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getCompareActions() {

        if (!isset($this->_compare_actions)) {
            $this->_compare_actions = [];
        }

        return $this->_compare_actions;
    }

    /**
     * @param string $value
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setCompareDateFrom($value) {

        $this->_compare_date_from = $value;

        return $this;
    }

    /**
     * @return string
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getCompareDateFrom() {

        return $this->_compare_date_from;
    }

    /**
     * @param string $value
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setCompareDateTo($value) {

        $this->_compare_date_to = $value;

        return $this;
    }

    /**
     * @return string
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getCompareDateTo() {

        return $this->_compare_date_to;
    }

    /**
     * @param int $value
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setCompareOption($value) {

        $this->_compare_date_option = (int) $value;

        return $this;
    }

    /**
     * @return int
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getCompareOption() {

        if (!isset($this->_compare_date_option)) {
            $this->_compare_date_option = static::DEFAULT_COMPARE_OPTION;
        }

        return $this->_compare_date_option;
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws PhenyxException
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setDateFormat($value) {

        if (!is_string($value)) {
            throw new PhenyxException('Date format must be a string');
        }

        $this->_date_format = $value;

        return $this;
    }

    /**
     * @return string
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getDateFormat() {

        if (!isset($this->_date_format)) {
            $this->_date_format = static::DEFAULT_DATE_FORMAT;
        }

        return $this->_date_format;
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws PhenyxException
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setDateFrom($value) {

        if (!isset($value) || $value == '') {
            $value = date('Y-m-d', strtotime('-31 days'));
        }

        if (!is_string($value)) {
            throw new PhenyxException('Date must be a string');
        }

        $this->_date_from = $value;

        return $this;
    }

    /**
     * @return false|string
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getDateFrom() {

        if (!isset($this->_date_from)) {
            $this->_date_from = date('Y-m-d', strtotime('-31 days'));
        }

        return $this->_date_from;
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws PhenyxException
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setDateTo($value) {

        if (!isset($value) || $value == '') {
            $value = date('Y-m-d');
        }

        if (!is_string($value)) {
            throw new PhenyxException('Date must be a string');
        }

        $this->_date_to = $value;

        return $this;
    }

    /**
     * @return false|string
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function getDateTo() {

        if (!isset($this->_date_to)) {
            $this->_date_to = date('Y-m-d');
        }

        return $this->_date_to;
    }

    /**
     * @param bool $value
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function setRTL($value) {

        $this->_rtl = (bool) $value;

        return $this;
    }

    /**
     * @param string $action
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function addAction($action) {

        if (!isset($this->_actions)) {
            $this->_actions = [];
        }

        $this->_actions[] = $action;

        return $this;
    }

    /**
     * @param string $action
     *
     * @return $this
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function addCompareAction($action) {

        if (!isset($this->_compare_actions)) {
            $this->_compare_actions = [];
        }

        $this->_compare_actions[] = $action;

        return $this;
    }

    /**
     * @return string
     *
     * @throws Exception
     * @throws SmartyException
     * @since   1.8.1.0
     * @version 1.8.5.0
     */
    public function generate() {

        $context = Context::getContext();
        $adminWebpath = str_ireplace(_SHOP_CORE_DIR_, '', _EPH_ROOT_DIR_);
        $adminWebpath = preg_replace('/^' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', '', $adminWebpath);
        $boTheme = ((Validate::isLoadedObject($context->employee)
            && $context->employee->bo_theme) ? $context->employee->bo_theme : 'blacktie');

        if (!file_exists(_EPH_BO_ALL_THEMES_DIR_ . $boTheme . DIRECTORY_SEPARATOR . 'template')) {
            $boTheme = 'blacktie';
        }

        if ($context->controller->ajax) {
            $html = '<script type="text/javascript" src="' . _ESHOP_JS_DIR_ . 'date-range-picker.js"></script>';
            $html .= '<script type="text/javascript" src="' . _ESHOP_JS_DIR_ . 'calendar.js"></script>';
        } else {
            $html = '';
            $context->controller->addJS(_ESHOP_JS_DIR_ . 'date-range-picker.js');
            $context->controller->addJS(_ESHOP_JS_DIR_ . 'calendar.js');
        }

        $this->tpl = $this->createTemplate($this->base_tpl);
        $this->tpl->assign(
            [
                'date_format'       => $this->getDateFormat(),
                'date_from'         => $this->getDateFrom(),
                'date_to'           => $this->getDateTo(),
                'compare_date_from' => $this->getCompareDateFrom(),
                'compare_date_to'   => $this->getCompareDateTo(),
                'actions'           => $this->getActions(),
                'compare_actions'   => $this->getCompareActions(),
                'compare_option'    => $this->getCompareOption(),
                'is_rtl'            => $this->isRTL(),
            ]
        );

        $html .= parent::generate();

        return $html;
    }

    /**
     * @return bool
     *
     * @since 1.8.1.0
     * @version 1.8.5.0
     */
    public function isRTL() {

        if (!isset($this->_rtl)) {
            $this->_rtl = Context::getContext()->language->is_rtl;
        }

        return $this->_rtl;
    }

}
