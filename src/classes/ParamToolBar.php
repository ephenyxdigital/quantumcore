<?php

/**
 * Class ParamToolBar
 *
 * Fluent builder for pqGrid (v6.x / jQuery) toolbar configuration.
 *
 * pqGrid toolbar structure:
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  toolbar: {                                                     │
 * │    cls: 'optional-extra-css-class',                            │
 * │    items: [                                                     │
 * │      { type: 'button',    label: '...', icon: '...', ... },    │
 * │      { type: 'label',     label: '...' },                      │
 * │      { type: 'select',    label: '...', options: [...] },      │
 * │      { type: 'input',     label: '...', value: '...' },        │
 * │      { type: 'separator' },                                     │
 * │    ]                                                            │
 * │  }                                                              │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * Improvements over original:
 *  - addButton()    : typed helper for clickable toolbar buttons.
 *  - addLabel()     : typed helper for static text labels.
 *  - addSeparator() : typed helper for visual dividers.
 *  - addSelect()    : typed helper for dropdown selects.
 *  - addInput()     : typed helper for text inputs.
 *  - addRaw()       : escape hatch — push a pre-built item array directly.
 *  - setCls()       : set an extra CSS class on the whole toolbar.
 *  - All helpers return $this for fluent chaining.
 *  - buildToolBar() now also includes the optional `cls` key.
 *
 * Listener note
 * ─────────────
 * pqGrid accepts two formats for toolbar button listeners:
 *   a) A raw JS function string  : 'function(evt, ui){ ... }'
 *   b) An object with event keys : '{ "click": function(evt, ui){ ... } }'
 * Pass whichever format you need as the $listener argument.
 *
 * Icon note
 * ─────────
 * Icons are standard jQuery UI icon classes, e.g. 'ui-icon-plus',
 * 'ui-icon-pencil', 'ui-icon-trash', 'ui-icon-disk', etc.
 * Font-Awesome classes are supported by the contextMenu lib but pqGrid
 * toolbar relies on jQuery UI icons by default.
 *
 * Usage example
 * ─────────────
 * $toolbar = new ParamToolBar();
 * $toolbar
 *     ->setCls('my-toolbar')
 *     ->addButton('Add',    "'ui-icon-plus'",   'function(evt,ui){ addRow(); }')
 *     ->addButton('Save',   "'ui-icon-disk'",   'function(evt,ui){ saveChanges(); }', 'changes', true)
 *     ->addSeparator()
 *     ->addButton('Export', "'ui-icon-document'", 'function(evt,ui){ exportGrid(); }');
 *
 * $paramGrid->toolbar = $toolbar->buildToolBar();
 *
 * @since 2.1.0.0
 */
class ParamToolBar {

    /** @var array Ordered list of toolbar item arrays */
    public $items = [];

    /** @var string|null Optional extra CSS class applied to the toolbar container */
    protected $cls = null;

    public function __construct() {}

    // ─────────────────────────────────────────────────────────────────────────
    // Fluent configuration helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Set an extra CSS class on the toolbar container element.
     *
     * @param string $cls CSS class name(s), e.g. 'pq-toolbar-export'.
     *
     * @return $this
     */
    public function setCls(string $cls): self {

        $this->cls = $cls;

        return $this;
    }

    /**
     * Add a clickable button to the toolbar.
     *
     * @param string      $label    Visible button label (plain text, not a JS string).
     * @param string|null $icon     jQuery UI icon class, e.g. 'ui-icon-plus'. Pass null for no icon.
     * @param string|null $listener Raw JS listener: a function string or an event-object string.
     *                              e.g. 'function(evt, ui){ addRow(); }'
     *                              or   '{ "click": function(evt, ui){ addRow(); } }'
     * @param string|null $cls      Extra CSS class for this button, e.g. 'changes'.
     * @param bool        $disabled Whether the button starts in a disabled state.
     *
     * @return $this
     */
    public function addButton(
        string  $label,
        ?string $icon     = null,
        ?string $listener = null,
        ?string $cls      = null,
        bool    $disabled = false
    ): self {

        $item = ['type' => "'button'", 'label' => "'" . addslashes($label) . "'"];

        if ($icon !== null) {
            $item['icon'] = "'" . $icon . "'";
        }

        if ($listener !== null) {
            $item['listener'] = $listener;
        }

        if ($cls !== null) {
            $item['cls'] = "'" . $cls . "'";
        }

        if ($disabled) {
            $item['options'] = "{ disabled: true }";
        }

        $this->items[] = $item;

        return $this;
    }

    /**
     * Add a static text label to the toolbar.
     *
     * @param string      $label The label text.
     * @param string|null $cls   Optional extra CSS class.
     *
     * @return $this
     */
    public function addLabel(string $label, ?string $cls = null): self {

        $item = ['type' => "'label'", 'label' => "'" . addslashes($label) . "'"];

        if ($cls !== null) {
            $item['cls'] = "'" . $cls . "'";
        }

        $this->items[] = $item;

        return $this;
    }

    /**
     * Add a visual separator between toolbar items.
     *
     * @return $this
     */
    public function addSeparator(): self {

        $this->items[] = ['type' => "'separator'"];

        return $this;
    }

    /**
     * Add a select (dropdown) widget to the toolbar.
     *
     * @param string      $label    Label shown beside the select.
     * @param array       $options  Associative array of value => display text pairs.
     *                              e.g. ['10' => '10 rows', '50' => '50 rows']
     * @param string|null $listener Raw JS change listener.
     * @param string|null $cls      Optional extra CSS class.
     *
     * @return $this
     */
    public function addSelect(
        string  $label,
        array   $options  = [],
        ?string $listener = null,
        ?string $cls      = null
    ): self {

        // Build the JS options array string: [{label:'x',value:'y'}, ...]
        $jsOptions = [];

        foreach ($options as $value => $text) {
            $jsOptions[] = "{label:'" . addslashes($text) . "', value:'" . addslashes((string) $value) . "'}";
        }

        $item = [
            'type'    => "'select'",
            'label'   => "'" . addslashes($label) . "'",
            'options' => '[' . implode(', ', $jsOptions) . ']',
        ];

        if ($listener !== null) {
            $item['listener'] = $listener;
        }

        if ($cls !== null) {
            $item['cls'] = "'" . $cls . "'";
        }

        $this->items[] = $item;

        return $this;
    }

    /**
     * Add a text input to the toolbar.
     *
     * @param string      $label       Label shown beside the input.
     * @param string      $placeholder Placeholder text (default empty).
     * @param string|null $listener    Raw JS listener (keyup, change, etc.).
     * @param string|null $cls         Optional extra CSS class.
     *
     * @return $this
     */
    public function addInput(
        string  $label,
        string  $placeholder = '',
        ?string $listener    = null,
        ?string $cls         = null
    ): self {

        $item = [
            'type'  => "'input'",
            'label' => "'" . addslashes($label) . "'",
        ];

        if ($placeholder !== '') {
            $item['attr'] = "{ placeholder: '" . addslashes($placeholder) . "' }";
        }

        if ($listener !== null) {
            $item['listener'] = $listener;
        }

        if ($cls !== null) {
            $item['cls'] = "'" . $cls . "'";
        }

        $this->items[] = $item;

        return $this;
    }

    /**
     * Push a fully pre-built item array directly.
     * Use this escape hatch for item types not covered by the helpers above,
     * or when you need fine-grained control over every property.
     *
     * @param array $item Raw pqGrid toolbar item array.
     *
     * @return $this
     */
    public function addRaw(array $item): self {

        $this->items[] = $item;

        return $this;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Output
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build and return the toolbar configuration array, ready for assignment
     * to ParamGrid::$toolbar.
     *
     * @return array
     */
    public function buildToolBar(): array {

        $config = ['items' => $this->items];

        if ($this->cls !== null) {
            $config['cls'] = "'" . $this->cls . "'";
        }

        return $config;
    }
}
