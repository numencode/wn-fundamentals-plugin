<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Bootstrap;

use Backend\Classes\Controller;
use Backend\Models\Preference as BackendPreference;
use Backend\Widgets\Form;
use Backend\Widgets\Lists;
use Config;
use Event;
use Lang;
use System\Classes\CombineAssets;
use URL;
use Winter\Storm\Support\Str;

/**
 * Backend-wide changes made by the plugin. Each one can be switched off in the plugin config
 * (numencode.fundamentals::backend.*).
 */
class BackendOverride
{
    public const ASSETS_CSS = 'plugins/numencode/fundamentals/assets/scss/_general.scss';

    public const ASSETS_JS = 'plugins/numencode/fundamentals/assets/js/general.js';

    public const PAGES_DEEP_LINKS_JS = '/plugins/numencode/fundamentals/assets/js/pages-deep-links.js';

    public function init(): void
    {
        if (static::enabled('assets')) {
            $this->initBackendAssets();
        }

        if (static::enabled('switch_column_icons')) {
            $this->initSwitchColumns();
        }

        if (static::enabled('pages_viewbag_primary_tabs')) {
            $this->initPagesViewBagTabs();
        }

        if (static::enabled('pages_deep_links') && plugin_exists('Winter.Pages')) {
            $this->initPagesDeepLinks();
        }
    }

    /**
     * Checks whether a backend change is enabled in the plugin config.
     */
    public static function enabled(string $feature): bool
    {
        return (bool) Config::get('numencode.fundamentals::backend.' . $feature, true);
    }

    /**
     * Adds the plugin's backend JS and CSS to every backend page.
     */
    protected function initBackendAssets(): void
    {
        Event::listen('backend.page.beforeDisplay', function (Controller $controller) {
            $controller->addCss(URL::to(CombineAssets::combine([base_path(static::ASSETS_CSS)])));
            $controller->addJs(URL::to(CombineAssets::combine([base_path(static::ASSETS_JS)])));
        });
    }

    /**
     * Renders "switch" list columns as a green check or a red cross with core's translated Yes/No label.
     */
    protected function initSwitchColumns(): void
    {
        Event::listen('backend.list.overrideColumnValue', function (Lists $list, $record, $column, $value) {
            if ($column->type !== 'switch') {
                return null;
            }

            // $value is already core's formatted label, so use the raw value (also resolves relation columns)
            return $list->getColumnValueRaw($record, $column)
                ? '<i class="icon-check list-switch-true"></i> ' . e(Lang::get('backend::lang.list.column_switch_true'))
                : '<i class="icon-times list-switch-false"></i> ' . e(Lang::get('backend::lang.list.column_switch_false'));
        });
    }

    /**
     * Winter.Pages: moves the page settings (viewBag fields) from the secondary tabs (sidebar) to the primary tabs.
     */
    protected function initPagesViewBagTabs(): void
    {
        Event::listen('backend.form.extendFieldsBefore', function (Form $form) {
            // instanceof doesn't autoload, so this is safe when Winter.Pages isn't installed
            if (!$form->model instanceof \Winter\Pages\Classes\Page) {
                return;
            }

            foreach ($form->secondaryTabs['fields'] ?? [] as $name => $config) {
                if (!Str::startsWith($name, 'viewBag')) {
                    continue;
                }

                $config['cssClass'] = trim(str_replace('secondary-tab', '', $config['cssClass'] ?? ''));

                unset($form->secondaryTabs['fields'][$name]);

                $form->tabs['fields'][$name] = $config;
            }
        }, 1000);
    }

    /**
     * Winter.Pages: keeps the editor URL in sync with the active tab (?type=page|menu|content&path=...), so it can be
     * refreshed, bookmarked or shared. The script switches itself off when Winter.Pages has the feature natively.
     */
    protected function initPagesDeepLinks(): void
    {
        \Winter\Pages\Controllers\Index::extend(function (Controller $controller) {
            $controller->bindEvent('page.beforeDisplay', function (string $action) use ($controller) {
                if ($action !== 'index') {
                    return;
                }

                // Core sets the backend user's locale only after this event
                BackendPreference::setAppLocale();

                $controller->addJs(static::PAGES_DEEP_LINKS_JS, [
                    'build'                     => 'NumenCode.Fundamentals',
                    'id'                        => 'numencode-pages-deep-links',
                    'data-unauthorized-message' => Lang::get('winter.pages::lang.object.unauthorized_type'),
                ]);
            });
        });
    }
}
