(function ($) {

    function render()
    {
        $('.section-field[data-set-from]').each(initDynamicSection);
        $('[data-control="numencode-translate-selector"]').each(initTranslatableSelector);
    }

    function initDynamicSection()
    {
        if ($(this).data('original')) return;

        var target = $(this).find('h4');
        var original = target.text();
        var pattern = $(this).data('set-from');

        $(this).data('original', original);

        var fields = pattern.match(/\[[a-z0-9_]+\]/gi) || [];

        for (var i=0; i< fields.length; i++) {
            if (fields[i] == '[original]') continue;

            var elm = $(this).closest('[data-control=formwidget]').find("[name$='" + fields[i] + "']");

            if (elm.prop('tagName') == 'INPUT') {
                elm.keyup(function () {
                    refreshDynamicSection(target, original, fields, pattern);
                });
            } else {
                elm.change(function () {
                    refreshDynamicSection(target, original, fields, pattern);
                });
            }
        }

        refreshDynamicSection(target, original, fields, pattern);
    }

    function refreshDynamicSection(target, original, fields, pattern)
    {
        var result = pattern;
        var replaced = false;

        for (var i=0; i < fields.length; i++) {
            if (fields[i] == '[original]') {
                result = result.replace(fields[i], original);
                continue;
            }

            var field = target.closest('[data-control=formwidget]').find("[name$='" + fields[i] + "']");
            var value = field.val();

            if (field.prop("tagName") == 'SELECT' && value) {
                value = field.find('option').filter(function () {
                    return this.value === value;
                }).text();
            }

            if (value) {
                result = result.replace(fields[i], value);
                replaced = true;
            }
        }

        if (!replaced) {
            target.text(original);
            return;
        }

        target.text(result.replace(/\[[a-z0-9_]+\]/gi, ''));
    }

    /*
     * Language selector for forms with translatable fields (partials/_translate_selector.htm).
     * Uses Winter.Translate's multilingual control API, so every field type updates correctly.
     */
    function initTranslatableSelector()
    {
        var $selector = $(this);

        if ($selector.data('initialized')) return;

        $selector.data('initialized', true);

        var $form = $selector.closest('form');
        var $locale = $selector.find('[data-translate-locale]');
        var $copy = $selector.find('[data-translate-copy]');

        // Every Translate field (mltext, mlricheditor, ...) holds a MultiLingual instance in its data
        function multilingualControls()
        {
            return $form.find('.field-multilingual').filter(function () {
                return !!$(this).data('oc.multilingual');
            });
        }

        $locale.on('change', function () {
            var locale = $(this).val();

            multilingualControls().each(function () {
                $(this).multiLingual('setLocale', locale);
            });
        });

        $copy.on('change', function () {
            var source = $(this).val();
            var target = $locale.val();

            // Reset the dropdown without running this handler again
            $(this).val('').trigger('change.select2');

            if (!source || !target || source === target) return;

            var message = String($selector.data('copy-confirm'))
                .replace(':source', source.toUpperCase())
                .replace(':target', target.toUpperCase());

            var copyAll = function () {
                multilingualControls().each(function () {
                    $(this).multiLingual('setLocale', target);
                    $(this).multiLingual('applyCopyLocale', source, '');
                });
            };

            if ($.wn && typeof $.wn.confirm === 'function') {
                $.wn.confirm(message, function (isConfirm) {
                    if (isConfirm) copyAll();
                });
            } else if (window.confirm(message)) {
                copyAll();
            }
        });
    }

    $(document).render(render);

}(jQuery));
