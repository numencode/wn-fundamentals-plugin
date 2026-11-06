/*
 * Relation repeater: "Expand all" / "Collapse all" buttons (option showToggleAll).
 * Uses the core repeater's expandAll() and collapseAll().
 */
+function ($) { "use strict";

    $(document).on('click', '[data-relationrepeater-toggle]', function (ev) {
        ev.preventDefault()

        var $button = $(this),
            repeater = $button
                .closest('[data-control="relationrepeater"]')
                .children('[data-control="fieldrepeater"]')
                .data('oc.repeater')

        if (!repeater) {
            return
        }

        if ($button.data('relationrepeater-toggle') === 'expand') {
            repeater.expandAll()
        }
        else {
            repeater.collapseAll()
        }
    })

}(window.jQuery);
