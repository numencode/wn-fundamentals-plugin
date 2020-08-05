<?php namespace NumenCode\Fundamentals\Bootstrap;

use URL;
use Event;
use ReflectionObject;
use System\Helpers\Cache;
use Backend\Facades\BackendAuth;
use Backend\Models\BrandSetting;
use System\Classes\CombineAssets;
use System\Classes\SettingsManager;

class BackendOverride
{
    public function init()
    {
        $this->initSettingsPage();
        $this->initCustomListColumns();

        view()->addNamespace('partials', dirname(__DIR__) . '/assets/partials');
    }

    protected function initSettingsPage()
    {
        Event::listen('backend.page.beforeDisplay', function ($controller, $action) {
            $generalCss = 'plugins/numencode/fundamentals/assets/scss/_general.scss';
            $generalJs = 'plugins/numencode/fundamentals/assets/js/general.js';

            $combinedCss = [base_path($generalCss)];
            $combinedJs = [base_path($generalJs)];

            $combinedCss = URL::to(CombineAssets::combine($combinedCss));
            $combinedJs = URL::to(CombineAssets::combine($combinedJs));

            $controller->addCss($combinedCss);
            $controller->addJs($combinedJs);

            if (get_class($controller) == 'System\Controllers\Settings' && $action == 'index') {
                $refObject = new ReflectionObject($controller);

                $refProperty = $refObject->getProperty('user');
                $refProperty->setAccessible(true);
                $refProperty->setValue($controller, BackendAuth::getUser());

                $items = SettingsManager::instance()->listItems('system');

                $controller->pageTitle = 'system::lang.settings.menu_label';

                return $controller->makeViewContent(numencode_partial('_settings.php', compact('items')));
            }
        });

        BrandSetting::saving(function() {
            Cache::instance()->clearCombiner();
        });
    }

    protected function initCustomListColumns()
    {
        Event::listen('backend.list.overrideColumnValue', function ($list, $record, $column, $value) {
            if ($column->type == 'switch') {
                return array_get($record, str_replace(['[', ']'], ['.', ''], $column->columnName)) ?
                    '<i style="color:#2ecc71;" class="icon-check"></i> Yes' :
                    '<i style="color:#d6220f;" class="icon-times"></i> No';
            }
        });
    }
}
