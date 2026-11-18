/*
 * Relation repeater (NumenCode\Fundamentals\FormWidgets\RelationRepeater):
 *
 * - "Expand all" / "Collapse all" buttons (option showToggleAll), using the core repeater's expandAll() and collapseAll().
 * - Item actions (option itemActions; settings in data-item-actions): copy, duplicate and publish/unpublish on each
 *   item, and "Paste item". A duplicate or pasted item is rendered by the server (onDuplicateItem). A copied item is
 *   kept in the browser's local storage, for repeaters with the same item form (formKey).
 */
+function ($) { "use strict";

    var CLIPBOARD_KEY = 'numencode.relationrepeater.clipboard'

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

    //
    // Item actions
    //

    function settings($wrapper) {
        try {
            return JSON.parse($wrapper.attr('data-item-actions') || '{}')
        }
        catch (e) {
            return {}
        }
    }

    function labels($wrapper) {
        return settings($wrapper).labels || {}
    }

    function repeaterOf($wrapper) {
        return $wrapper.children('[data-control="fieldrepeater"]')
    }

    function items($wrapper) {
        return repeaterOf($wrapper).children('.field-repeater-items').children('.field-repeater-item')
    }

    // The item's index in the posted field (from its remove button)
    function itemIndex($item) {
        var match = String($item.children('.repeater-item-remove').find('[data-repeater-remove]').attr('data-request-data') || '')
            .match(/_repeater_index':\s*'([^']*)'/)

        return match ? match[1] : null
    }

    // The item's own field (not one of a nested repeater)
    function ownField($item, name) {
        return $item.find('[data-field-name="' + name + '"]').filter(function () {
            return $(this).closest('.field-repeater-item')[0] === $item[0]
        }).first()
    }

    function publishInput($wrapper, $item) {
        var field = settings($wrapper).publishField

        return field ? ownField($item, field).find('input[type="checkbox"]').first() : $()
    }

    function addActions($wrapper) {
        var config = settings($wrapper),
            text = labels($wrapper)

        items($wrapper).each(function () {
            var $item = $(this).addClass('relationrepeater-item')

            if ($item.children('.relationrepeater-item-actions').length) {
                updatePublished($wrapper, $item)
                return
            }

            var $actions = $('<div class="relationrepeater-item-actions">')

            $('<button type="button" data-relationrepeater-action="copy"><i class="icon-clipboard"></i></button>')
                .attr('title', text.copy || 'Copy').appendTo($actions)
            $('<button type="button" data-relationrepeater-action="duplicate"><i class="icon-copy"></i></button>')
                .attr('title', text.duplicate || 'Duplicate').appendTo($actions)

            if (config.publishField && publishInput($wrapper, $item).length) {
                $('<button type="button" data-relationrepeater-action="publish"><i class="icon-eye"></i></button>')
                    .appendTo($actions)
            }

            $actions.insertAfter($item.children('.repeater-item-collapsed-title'))
            updatePublished($wrapper, $item)
        })
    }

    // Unpublished items are dimmed and marked; the eye button shows the state
    function updatePublished($wrapper, $item) {
        var $input = publishInput($wrapper, $item),
            text = labels($wrapper),
            $actions = $item.children('.relationrepeater-item-actions')

        if (!$input.length) {
            return
        }

        var unpublished = !$input.prop('checked')

        $item.toggleClass('relationrepeater-item--unpublished', unpublished)
        $actions.find('[data-relationrepeater-action="publish"]')
            .attr('title', unpublished ? (text.publish || 'Publish') : (text.unpublish || 'Unpublish'))
            .find('i').attr('class', unpublished ? 'icon-eye-slash' : 'icon-eye')

        $actions.children('.relationrepeater-item-label').remove()

        if (unpublished) {
            $('<span class="relationrepeater-item-label">').text(text.unpublished || 'Unpublished').prependTo($actions)
        }
    }

    function wrapperOf(element) {
        return $(element).closest('[data-control="relationrepeater"][data-item-actions]')
    }

    // Publish / unpublish: toggles the item's publish switch
    $(document).on('click', '[data-relationrepeater-action="publish"]', function (ev) {
        ev.preventDefault()
        ev.stopPropagation()

        var $wrapper = wrapperOf(this),
            $item = $(this).closest('.field-repeater-item'),
            $input = publishInput($wrapper, $item)

        $input.prop('checked', !$input.prop('checked')).trigger('change')
        updatePublished($wrapper, $item)
    })

    // The switch changed in the item's form
    $(document).on('change', '[data-control="relationrepeater"][data-item-actions] .relationrepeater-item input[type="checkbox"]', function () {
        var $item = $(this).closest('.relationrepeater-item')

        updatePublished(wrapperOf($item), $item)
    })

    // Inserts an item rendered by onDuplicateItem
    function insertItem($wrapper, data, $after) {
        var $new = $(String(data.item || '')).filter('li.field-repeater-item'),
            $list = repeaterOf($wrapper).children('.field-repeater-items')

        if (!$new.length) {
            return
        }

        if ($after) {
            $new.insertAfter($after)
        }
        else if ($list.children('.field-repeater-add-item').length) {
            $new.insertBefore($list.children('.field-repeater-add-item'))
        }
        else {
            $new.appendTo($list)
        }

        $new.trigger('render')
        repeaterOf($wrapper).closest('[data-field-name]').trigger('change.oc.formwidget')
        $new.find(':input').first().trigger('change')
        $new[0].scrollIntoView({block: 'nearest'})
    }

    $(document).on('click', '[data-relationrepeater-action="duplicate"]', function (ev) {
        ev.preventDefault()
        ev.stopPropagation()

        var $wrapper = wrapperOf(this),
            $item = $(this).closest('.field-repeater-item'),
            index = itemIndex($item)

        if (index === null) {
            return
        }

        $item.request(settings($wrapper).handler, {
            data: {_repeater_source: index},
            success: function (data) {
                insertItem($wrapper, data, $item)
            }
        })
    })

    //
    // Copy and paste
    //

    function readClipboard() {
        try {
            var data = JSON.parse(window.localStorage.getItem(CLIPBOARD_KEY) || 'null')

            return data && data.values && data.formKey ? data : null
        }
        catch (e) {
            return null
        }
    }

    // The item's values as the form would post them, relative to the item (its own and nested inputs)
    function itemValues($wrapper, $item) {
        var index = itemIndex($item),
            prefix = settings($wrapper).field + '[' + index + ']',
            values = {}

        if (index === null) {
            return null
        }

        // Rich editors write their content to their textarea before a request
        $item.closest('form').trigger('oc.beforeRequest')

        $.each($item.find(':input').serializeArray(), function (i, input) {
            if (input.name.indexOf(prefix + '[') !== 0) {
                return
            }

            var keys = (input.name.slice(prefix.length).match(/\[[^\]]*\]/g) || []).map(function (key) {
                    return key.slice(1, -1)
                }),
                target = values

            keys.forEach(function (key, position) {
                if (key === '') {
                    if (!Array.isArray(target)) {
                        return
                    }

                    key = target.length
                }

                if (position === keys.length - 1) {
                    target[key] = input.value
                }
                else {
                    if (typeof target[key] !== 'object' || target[key] === null) {
                        target[key] = keys[position + 1] === '' ? [] : {}
                    }

                    target = target[key]
                }
            })
        })

        return values
    }

    function updatePasteButtons() {
        var clipboard = readClipboard()

        $('[data-control="relationrepeater"][data-item-actions]').each(function () {
            var $wrapper = $(this),
                available = !!clipboard && clipboard.formKey === settings($wrapper).formKey,
                text = labels($wrapper)

            $wrapper.find('> .relationrepeater-toggle-all > [data-relationrepeater-paste]')
                .toggle(available)
                .attr('title', available ? String(text.paste_title || ':title').replace(':title', clipboard.title || '') : '')
            $wrapper.find('> .relationrepeater-toggle-all > [data-relationrepeater-paste-clear]').toggle(available)
        })
    }

    $(document).on('click', '[data-relationrepeater-action="copy"]', function (ev) {
        ev.preventDefault()
        ev.stopPropagation()

        var $wrapper = wrapperOf(this),
            $item = $(this).closest('.field-repeater-item'),
            values = itemValues($wrapper, $item),
            title = $.trim($item.children('.repeater-item-collapsed-title').text())

        if (!values) {
            return
        }

        try {
            window.localStorage.setItem(CLIPBOARD_KEY, JSON.stringify({
                formKey: settings($wrapper).formKey,
                title: title,
                values: values
            }))
        }
        catch (e) {
            return
        }

        updatePasteButtons()

        if ($.wn && $.wn.flashMsg) {
            $.wn.flashMsg({text: labels($wrapper).copied || 'Copied', class: 'success', interval: 3})
        }
    })

    $(document).on('click', '[data-relationrepeater-paste]', function () {
        var $wrapper = $(this).closest('[data-control="relationrepeater"]'),
            clipboard = readClipboard()

        if (!clipboard || clipboard.formKey !== settings($wrapper).formKey) {
            return
        }

        $(this).request(settings($wrapper).handler, {
            data: {
                _repeater_paste: JSON.stringify(clipboard.values),
                _repeater_paste_form: clipboard.formKey
            },
            success: function (data) {
                insertItem($wrapper, data, null)
            }
        })
    })

    $(document).on('click', '[data-relationrepeater-paste-clear]', function () {
        try {
            window.localStorage.removeItem(CLIPBOARD_KEY)
        }
        catch (e) {
            // Storage not available: nothing was copied either
        }

        updatePasteButtons()
    })

    // Copied in another browser tab
    $(window).on('storage', function (ev) {
        if (ev.originalEvent && ev.originalEvent.key === CLIPBOARD_KEY) {
            updatePasteButtons()
        }
    })

    function update() {
        $('[data-control="relationrepeater"][data-item-actions]').each(function () {
            addActions($(this))
        })

        updatePasteButtons()
    }

    $(document).on('render', update)
    $(update)

}(window.jQuery);
