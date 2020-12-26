<?php namespace NumenCode\Fundamentals\Bootstrap;

use Event;
use Backend\Widgets\Form;
use RainLab\Pages\Classes\Page;

class ConfigOverride
{
    protected static $overrides = [];

    protected static $globalOverrides = [];

    public static function extendFields($class, $callback)
    {
        static::extend('fields.yaml', $class, $callback);
    }

    public static function extendColumns($class, $callback)
    {
        static::extend('columns.yaml', $class, $callback);
    }

    public static function extendImportColumns($class, $callback)
    {
        static::extend('columns_import.yaml', $class, $callback);
    }

    public static function extendExportColumns($class, $callback)
    {
        static::extend('columns_export.yaml', $class, $callback);
    }

    public static function extendImportExport($class, $callback)
    {
        static::extend('config_import_export.yaml', $class, $callback);
    }

    public static function extendAll($callback)
    {
        static::$globalOverrides[] = $callback;
    }

    public static function extend($file, $class, $callback)
    {
        $key = static::filePath($file, $class);

        if (!isset(static::$overrides[$key])) {
            static::$overrides[$key] = [];
        }

        static::$overrides[$key][] = $callback;
    }

    private static function filePath($file, $class)
    {
        $path = str_replace('\\', '/', strtolower($class) . '/' . $file);

        if (starts_with($path, ['system/', 'backend/', 'cms/'])) {
            $path = '/modules/' . $path;
        } else {
            $path = '/plugins/' . $path;
        }

        return $path;
    }

    public function init()
    {
        Event::listen('system.extendConfigFile', function ($publicFile, $config) {
            $hasOverrides = false;
            $key = strtolower($publicFile);

            if (isset(static::$overrides[$key])) {
                $callbacks = static::$overrides[$key];

                foreach ($callbacks as $callback) {
                    if ($result = $callback($config)) {
                        $config = $result;
                        $hasOverrides = true;
                    }
                }
            }

            if (!empty(static::$globalOverrides)) {
                foreach (static::$globalOverrides as $callback) {
                    if ($result = $callback($publicFile, $config)) {
                        $config = $result;
                        $hasOverrides = true;
                    }
                }
            }

            if ($hasOverrides) {
                return $config;
            }
        });

        $this->extendPagesPlugin();
    }

    protected function extendPagesPlugin()
    {
        Event::listen('backend.form.extendFieldsBefore', function (Form $form) {
            if (get_class($form->model) != Page::class) {
                return;
            }

            foreach ((array)$form->secondaryTabs['fields'] as $key => $value) {
                if (starts_with($key, 'viewBag')) {
                    $value['cssClass'] = trim(str_replace('secondary-tab', '', $value['cssClass']));

                    unset($form->secondaryTabs['fields'][$key]);

                    $form->tabs['fields'][$key] = $value;
                }
            }
        }, 1000);
    }
}
