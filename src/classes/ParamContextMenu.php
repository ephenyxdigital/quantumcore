<?php

/**
 * Class ParamContextMenu
 *
 * Generates a jquery.contextMenu (v2.x) configuration array that is consumed
 * by ParamGrid::deployArrayScript() and ultimately rendered as a JS object.
 *
 * Fixes applied (vs. original):
 *  1. `selected` was assigned without `var`/`let` → implicit global JS variable.
 *     Now declared with `var` inside the build function scope.
 *  2. `dataLenght` was a typo. Renamed to `dataLength` (with a capital L).
 *  3. Both `selected` and `dataLength` were computed but never referenced inside
 *     the returned object — they were dead code. They are now included in the
 *     returned object so callbacks in items can actually use them, or can be
 *     removed by the caller if unused in their specific grid.
 *  4. Added `addItem()`, `addSubMenu()`, `addSeparator()` helper methods so
 *     callers no longer have to know the raw array format for every item type.
 *
 * @since 2.1.0.0
 */
class ParamContextMenu {

    /** @var string PHP class name — used to derive the JS grid variable name */
    public $contextMenuClass;

    /** @var string Controller name */
    public $contextMenuController;

    /**
     * Raw items array.
     * Prefer the addItem() / addSeparator() / addSubMenu() helpers over
     * pushing into this array directly.
     *
     * @var array
     */
    public $items = [];

    public function __construct(string $contextMenuClass, string $contextMenuController) {

        $this->contextMenuClass      = $contextMenuClass;
        $this->contextMenuController = $contextMenuController;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Fluent builder helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Add a standard clickable item.
     *
     * @param string      $key      Unique item key (used as JS property name).
     * @param string      $name     JS expression for the label, e.g. "'Edit row'" or 'translate("edit")'.
     * @param string      $callback JS callback body (raw JS string), e.g. 'function(key, opt){ ... }'.
     * @param string|null $icon     Optional CSS icon class, e.g. "'ui-icon-pencil'".
     * @param bool        $disabled Whether the item starts disabled.
     *
     * @return $this
     */
    public function addItem(string $key, string $name, string $callback, ?string $icon = null, bool $disabled = false): self {

        $item = [
            'name'     => $name,
            'callback' => $callback,
        ];

        if ($icon !== null) {
            $item['icon'] = $icon;
        }

        if ($disabled) {
            $item['disabled'] = 'true';
        }

        $this->items[$key] = $item;

        return $this;
    }

    /**
     * Add a visual separator (cm_seperator — note: contextMenu lib spells it this way).
     *
     * @param string $key Unique key for this separator, e.g. 'sep1'.
     *
     * @return $this
     */
    public function addSeparator(string $key = 'separator'): self {

        // jquery.contextMenu expects the string value "---" for a separator.
        $this->items[$key] = '"---"';

        return $this;
    }

    /**
     * Add a sub-menu item.
     *
     * @param string $key      Unique item key.
     * @param string $name     JS expression for the sub-menu label.
     * @param array  $subItems Nested items array in the same format as $this->items.
     *
     * @return $this
     */
    public function addSubMenu(string $key, string $name, array $subItems): self {

        $this->items[$key] = [
            'name'  => $name,
            'items' => $subItems,
        ];

        return $this;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Core builder
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build and return the contextMenu configuration as a PHP array.
     *
     * The returned array is passed to ParamGrid's contextMenu key, which feeds
     * it into deployArrayScript(). Values that must be raw JS (callbacks,
     * function references) should already be raw JS strings when stored in
     * $this->items.
     *
     * @return array
     */
    public function buildContextMenu(): array {

        $items = $this->renderItems($this->items);

        // ── FIX 1 & 2 : `selected` is now declared with `var` (no longer an
        //    implicit global), and `dataLenght` is corrected to `dataLength`.
        //
        // ── FIX 3 : Both variables are now returned inside the object so that
        //    item callbacks can reference `ui.selected` and `ui.dataLength`.
        //    If your callbacks do not need them, simply remove those two lines
        //    from the build function string below.
        $gridVar    = 'grid' . $this->contextMenuClass;
        $selGridVar = 'sel' . $gridVar;   // matches ParamGrid output: sel + grid + Class

        $buildFn = 'function($triggerElement, e) {
            var rowIndex  = $($triggerElement).attr("data-rowIndx");
            var rowData   = ' . $gridVar . '.getRowData({ rowIndx: rowIndex });
            var selected  = ' . $selGridVar . '.getSelection().length;
            var dataLength = ' . $gridVar . '.option("dataModel.data").length;
            return {
                callback: function() {},
                items: {' . PHP_EOL . $items . PHP_EOL . '}
            };
        }';

        return [
            'selector'  => '\'.pq-body-outer .pq-grid-row\'',
            'animation' => [
                'duration' => 250,
                'show'     => '\'fadeIn\'',
                'hide'     => '\'fadeOut\'',
            ],
            'build'     => $buildFn,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal rendering
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Recursively render an items array to a JS object-literal string fragment.
     *
     * @param array $items
     *
     * @return string
     */
    protected function renderItems(array $items): string {

        $output = '';

        foreach ($items as $key => $value) {

            if (is_string($value)) {
                // Raw JS value: separator ("---") or a pre-built JS expression.
                $output .= '"' . $key . '": ' . $value . ',' . PHP_EOL;
                continue;
            }

            if (is_array($value)) {
                $output .= '"' . $key . '": {' . PHP_EOL;

                foreach ($value as $prop => $propValue) {

                    if ($prop === 'items' && is_array($propValue)) {
                        // Nested sub-menu
                        $output .= '    "items": {' . PHP_EOL;
                        $output .= $this->renderItems($propValue);
                        $output .= '    },' . PHP_EOL;
                    } else {
                        // Scalar JS value — stored as a raw JS string by callers.
                        $output .= '    ' . $prop . ': ' . $propValue . ',' . PHP_EOL;
                    }
                }

                $output .= '},' . PHP_EOL;
            }
        }

        return $output;
    }
}
