(function ($) {

    let initialExecution = true;

    function init()
    {
        //
    }

    function render()
    {
        $('.repeater-collapsible').each(initCollapsibleRepeater);
        $('.section-field[data-set-from]').each(initDynamicSection);
        $('.translatable-selector').each(initTranslatableSelector);

        initialExecution = false;
    }

    function initCollapsibleRepeater()
    {
        var collapsible = $(this);

        if (!collapsible.hasClass('collapsible'))
        {
            collapsible.addClass('collapsible').prepend(
                '<div class="collapsible-controls">' +
                '<div class="btn-group collapsible-controls">' +
                '<button class="btn btn-sm btn-link js-expand"><i class="icon-expand"></i> Expand all</button>' +
                '<button class="btn btn-sm btn-link js-collapse"><i class="icon-compress"></i> Collapse all</button>' +
                '</div>' +
                '</div>'
            );

            collapsible.find('.js-expand').click(function (e) {
                e.preventDefault();
                collapsible.find('.section-field.collapsed, .form-section.collapsed').trigger('click');
            });

            collapsible.find('.js-collapse').click(function (e) {
                e.preventDefault();
                collapsible.find('.section-field:not(.collapsed), .form-section:not(.collapsed)').trigger('click');
            });
        }

        collapsible.find('.section-field').each(initCollapsibleRepeaterItems);
    }

    function initDynamicSection()
    {
        if ($(this).data('original')) return;

        var target = $(this).find('h4');
        var original = target.text();
        var pattern = $(this).data('set-from');

        $(this).data('original', original);

        var fields = pattern.match(/\[[a-z0-9_]+\]/gi);

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
                value = field.find('option[value="' + value + '"]').text();
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

    function initCollapsibleRepeaterItems()
    {
        var item = $(this).find('.field-section:first');
        var form = item.closest('.field-repeater-item');
        var collapsible = $(this).closest('.repeater-collapsible');

        if (item.hasClass('is-collapsible')) return;

        item.addClass('is-collapsible');
        form.addClass('is-collapsible');

        $(this).on('click', function () {
            $(this)
                .toggleClass('collapsed')
                .nextUntil('.section-field').toggle()
        });

        var shouldInitializeCollapsed = initialExecution || $(this).parents('.modal-body').length > 0;

        if (shouldInitializeCollapsed && ! collapsible.hasClass('repeater-open') && collapsible.find('.section-field').length > 1) {
            $(this).addClass('collapsed').nextUntil('.section-field').hide();
        }
    }

    function initTranslatableSelector()
    {
        var selector = $(this);

        if (selector.hasClass('translatable-initialized')) return;

        selector.addClass('translatable-initialized');

        selector.find('#js-lang-select').change(function () {
            $(this).closest('form').find('[data-switch-locale="' + $(this).val() + '"]').click();
        });

        selector.find('#js-lang-copy').change(function () {
            var target = selector.find('#js-lang-select').val();
            var source = $(this).val();

            if (!source) return;

            if (confirm("You will override all " + target.toUpperCase() + " translations with " + source.toUpperCase() + ".\n\nDo you wish to continue?")) {
                // Copy all of the translations
                $('[data-locale-value="' + source + '"]').each(function () {
                    var targetElm = $(this).parent().find('[data-locale-value="' + target + '"]');
                    targetElm.val($(this).val());
                });

                // Switch to new language
                $(this).closest('form').find('[data-active-locale]').text(source);
                $(this).closest('form').find('[data-switch-locale="' + target + '"]').click();
            }

            $(this).val('').trigger('change');
        });
    }

    $(document).render(render);
    $(document).ready(init);

}(jQuery));
