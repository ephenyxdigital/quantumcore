<?php

/**
 * Class TreeToolbarLinkCore
 *
 * @since 1.9.1.0
 */
class TreeToolbarLink extends TreeToolbarButton implements ITreeToolbarButton {
    // @codingStandardsIgnoreStart
    private $_action;
    private $_icon_class;
    private $_link;
    protected $_template = 'tree_toolbar_link.tpl';
    // @codingStandardsIgnoreEnd

    /**
     * TreeToolbarLinkCore constructor.
     *
     * @param      $label
     * @param null $link
     * @param null $action
     * @param null $iconClass
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function __construct($label, $link, $action = null, $iconClass = null) {
        parent::__construct($label);

        $this->setLink($link);
        $this->setAction($action);
        $this->setIconClass($iconClass);
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setAction($value) {
        return $this->setAttribute('action', $value);
    }

    /**
     * @return null
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getAction() {
        return $this->getAttribute('action');
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setIconClass($value) {
        return $this->setAttribute('icon_class', $value);
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getIconClass() {
        return $this->getAttribute('icon_class');
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function setLink($value) {
        return $this->setAttribute('link', $value);
    }

    /**
     * @return mixed
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public function getLink() {
        return $this->getAttribute('link');
    }
}
