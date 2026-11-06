<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Traits;

use Cms\Classes\Partial;
use Cms\Classes\Theme;
use Lang;

/**
 * Selectable, theme-overridable layouts for CMS components.
 *
 * A component using this trait can render one of several layouts (partials). The layouts are
 * looked up in the theme folder named after the component class, e.g. themes/<theme>/partials/gallery/
 * for a Gallery component – regardless of the alias the component has on a page – and then in the
 * component's own folder. Core's overrides in themes/<theme>/partials/<alias>/ keep working too.
 *
 * Usage:
 *
 *     class Gallery extends ComponentBase
 *     {
 *         use ComponentRenderer;
 *
 *         public function defineProperties()
 *         {
 *             return [
 *                 'layout' => ['title' => 'Layout', 'type' => 'dropdown', 'default' => '@default'],
 *             ];
 *         }
 *     }
 *
 * and enable layout selection (e.g. in a theme plugin's boot()):
 *
 *     Gallery::$overrideLayout = true;
 *
 * The "layout" dropdown then lists the component's partials and the theme's partials in that folder
 * (partials starting with "_" are skipped), and the selected one is rendered.
 */
trait ComponentRenderer
{
    /**
     * When enabled, the partial selected in the "layout" property is rendered instead of "default".
     */
    public static bool $overrideLayout = false;

    /**
     * Renders the component. Called by the CMS when the component is rendered with {% component %}.
     */
    public function onRender()
    {
        $layout = static::$overrideLayout ? (string) $this->property('layout') : '';

        return $this->renderLayout($layout !== '' ? $layout : 'default') ?: ' ';
    }

    /**
     * Renders a layout partial by name ("services" or "@services").
     */
    public function renderLayout(string $layout): mixed
    {
        $name = ltrim($layout, '@');

        // 1. Theme override in the folder named after the component class
        if ($this->controller && $theme = $this->controller->getTheme()) {
            $override = $this->getLayoutFolder() . '/' . $name;

            if (Partial::loadCached($theme, $override) !== null) {
                $this->controller->setComponentContext($this);

                try {
                    return $this->controller->renderPartial($override, ['__SELF__' => $this]);
                } finally {
                    $this->controller->setComponentContext(null);
                }
            }
        }

        // 2. Core: theme override in partials/<alias>/, then the component's own partial
        return $this->renderPartial('@' . $name);
    }

    /**
     * Name of the theme partials folder with this component's layouts (the lowercase class name).
     */
    public function getLayoutFolder(): string
    {
        return strtolower(class_basename($this));
    }

    /**
     * Options for the "layout" dropdown property.
     *
     * @return array<string, string>
     */
    public function getLayoutOptions(): array
    {
        $names = $this->findLayouts($this->getPath());

        if ($theme = Theme::getActiveTheme()) {
            $names = array_merge($names, $this->findLayouts($theme->getPath() . '/partials/' . $this->getLayoutFolder()));
        }

        $names = array_unique(['default', ...$names]);
        $options = [];

        foreach ($names as $name) {
            $options['@' . $name] = $name === 'default'
                ? Lang::get('numencode.fundamentals::lang.component.layout_default')
                : ucfirst(str_replace(['_', '-'], ' ', $name));
        }

        return $options;
    }

    /**
     * Returns the names of the layout partials (.htm, not starting with "_") in a directory.
     *
     * @return array<int, string>
     */
    protected function findLayouts(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $names = [];

        foreach (glob($directory . '/*.htm') ?: [] as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (!str_starts_with($name, '_') && !str_starts_with($name, '.')) {
                $names[] = $name;
            }
        }

        sort($names);

        return $names;
    }
}
