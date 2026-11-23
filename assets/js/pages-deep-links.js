/*
 * Winter.Pages: deep-linkable editor tabs.
 *
 * Keeps the URL of the Pages editor (static pages, menus, content) in sync with the active tab
 * (?type=page|menu|content&path=<object path>), so the URL can be refreshed, bookmarked or shared and
 * reopens the same object.
 *
 * A stopgap until Winter.Pages has the feature itself: when it does ($.wn.pagesPage.openObjectFromUrl
 * exists), this script does nothing.
 */
(function ($) {
    'use strict';

    var SECTIONS = {page: 'pages', menu: 'menus', content: 'content'},
        script = document.currentScript || document.getElementById('numencode-pages-deep-links'),
        unauthorizedMessage = script ? String(script.getAttribute('data-unauthorized-message') || '') : '',
        $masterTabs,
        $sidePanel;

    /*
     * Loaded before pages-page.js, so wait until its ready handler has created $.wn.pagesPage and bound
     * its own listeners (tab setup, the objectPath update after saving), which must run before ours.
     */
    $(document).ready(function () {
        setTimeout(init, 0);
    });

    function init()
    {
        var pagesPage = $.wn && $.wn.pagesPage;

        // Winter.Pages has the feature natively: let it handle the URL
        if (!pagesPage || typeof pagesPage.openObjectFromUrl === 'function') {
            return;
        }

        $masterTabs = $('#pages-master-tabs');
        $sidePanel = $('#pages-side-panel');

        if (!$masterTabs.length || !$masterTabs.data('oc.tab')) {
            return;
        }

        $masterTabs.on('shown.bs.tab', onTabShown);
        $masterTabs.on('afterAllClosed.oc.tab', function () {
            setUrl(null, null);
        });
        $(document).on('ajaxSuccess', '#pages-master-tabs form', onAjaxSuccess);

        openObjectFromUrl();
    }

    function onTabShown(e)
    {
        // Also fired by the secondary tabs inside the editor
        if ($(e.target).closest('[data-control=tab]').attr('id') !== 'pages-master-tabs') {
            return;
        }

        updateUrlFromPane($($(e.target).attr('data-target')));
    }

    function onAjaxSuccess(e, context)
    {
        var $tabPane = $(e.currentTarget).closest('.tab-pane');

        // A new object gets its path on the first save, so the URL can now point to it
        if (context && context.handler === 'onSave' && $tabPane.hasClass('active')
            && $tabPane.parent().parent().is($masterTabs)) {
            updateUrlFromPane($tabPane);
        }
    }

    function getObjectForm(type)
    {
        return $('form[data-object-type=' + type + ']', $sidePanel);
    }

    function getTabId(type, path)
    {
        return type + '-' + $('input[name=theme]', getObjectForm(type)).val() + '-' + path;
    }

    /*
     * Opens an object like a click in the sidebar does. If the object is already opened, activates its tab.
     * Returns the AJAX request promise, or null if no request was needed.
     */
    function openObject(type, path)
    {
        var $form = getObjectForm(type),
            data = {
                type: type,
                theme: $('input[name=theme]', $form).val(),
                path: path
            },
            tabId = data.type + '-' + data.theme + '-' + data.path;

        if ($masterTabs.data('oc.tab').goTo(tabId)) {
            return null;
        }

        $.wn.stripeLoadIndicator.show();

        return $form.request('onOpen', {
            data: data
        }).done(function (data) {
            $masterTabs.ocTab('addTab', data.tabTitle, data.tab, tabId, $form.data('type-icon'));
        }).always(function () {
            $.wn.stripeLoadIndicator.hide();
        });
    }

    /*
     * Opens the object referenced by the "type" and "path" URL query parameters, if any.
     */
    function openObjectFromUrl()
    {
        var params = new URLSearchParams(window.location.search),
            type = params.get('type'),
            path = params.get('path'),
            sidePanelTab = $('[data-control=layout-sidepanel]').data('oc.sidePanelTab'),
            request;

        if (!type && !path) {
            return;
        }

        // Tab lookups build attribute selectors from the path; reject characters that would break them
        if (!SECTIONS.hasOwnProperty(type) || !path || /["\\\r\n\f]/.test(path)) {
            setUrl(null, null);
            return;
        }

        // The sidebar section is only rendered when the user has access to the object type
        if (!getObjectForm(type).length) {
            $.wn.flashMsg({
                text: unauthorizedMessage.replace(':type', type),
                'class': 'error'
            });
            setUrl(null, null);
            return;
        }

        if (sidePanelTab) {
            sidePanelTab.displayTab($('#layout-sidenav li[data-menu-item=' + SECTIONS[type] + ']'));
        }

        request = openObject(type, path);

        if (request) {
            request.done(function () {
                revealSidebarItem(type, path);
            }).fail(function () {
                // Another tab may have been opened while the request was pending; let it own the URL
                updateUrlFromPane($('> div.tab-content > div.tab-pane.active', $masterTabs));
            });
        } else {
            revealSidebarItem(type, path);
        }
    }

    /*
     * Expands any collapsed sidebar groups (parent pages, content folders) containing the given object,
     * so the active item is visible.
     */
    function revealSidebarItem(type, path)
    {
        var dataId = getTabId(type, path),
            $item = $('li', getObjectForm(type)).filter(function () {
                return $(this).attr('data-id') === dataId;
            });

        $($item.parents('li[data-status=collapsed]').get().reverse()).each(function () {
            var $group = $(this),
                $control = $group.closest('[data-control=treeview], [data-control=filelist]');

            if ($control.data('control') === 'treeview') {
                $control.treeView('expandGroup', $group);
            } else {
                $control.fileList('expandGroup', $group);
            }
        });
    }

    /*
     * Points the URL to the object edited in the given master tab pane,
     * or to the bare editor URL if the object is not saved yet.
     */
    function updateUrlFromPane($pane)
    {
        var $form = $('> form', $pane),
            type = $('> input[name=objectType]', $form).val(),
            path = $('> input[name=objectPath]', $form).val();

        if (type && path) {
            setUrl(type, path);
        } else {
            setUrl(null, null);
        }
    }

    /*
     * Replaces the "type" and "path" URL query parameters, preserving any others. Passing null removes them.
     */
    function setUrl(type, path)
    {
        if (!window.history || !window.history.replaceState) {
            return;
        }

        var url = new URL(window.location.href),
            newUrl;

        if (type && path) {
            url.searchParams.set('type', type);
            url.searchParams.set('path', path);
        } else {
            url.searchParams.delete('type');
            url.searchParams.delete('path');
        }

        // Slashes are valid in a query string, keep them readable for subfolder paths
        newUrl = url.toString().replace(/%2F/gi, '/');

        if (newUrl !== window.location.href) {
            window.history.replaceState(window.history.state, '', newUrl);
        }
    }

})(window.jQuery);
