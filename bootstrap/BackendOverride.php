<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Bootstrap;

use Backend\Classes\Controller;
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
}
