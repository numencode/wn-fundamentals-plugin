<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Bootstrap;

use Backend\Widgets\Form;
use Config;
use Event;
use Lang;
use NumenCode\Fundamentals\Behaviors\RelationableModel;
use NumenCode\Fundamentals\Classes\RepeaterForm;
use Winter\Storm\Database\Model;
use Winter\Translate\Classes\EventRegistry;
use Winter\Pages\Plugin as PagesPlugin;
use Winter\Translate\Models\Locale;

/**
 * Winter.Translate support (only active when the Translate plugin is installed and enabled):
 *
 * - Translatable fields inside relationrepeater items become multilingual (Translate itself skips
 *   nested forms).
 * - Backend forms with translatable fields get a language selector at the top, which switches all
 *   fields of the form at once and can copy all translations from another language.
 * - With Winter.Pages: saving a page, menu, content, snippet or CMS template clears the Pages caches of every
 *   enabled locale, not only the backend's (config translate.pages_cache_all_locales).
 * - Translations that are only whitespace are saved empty, so they fall back to the default language
 *   (config translate.clear_blank_translations).
 */
class TranslateIntegration
{
    public const TRANSLATABLE_BEHAVIOR = 'Winter.Translate.Behaviors.TranslatableModel';

    public const SELECTOR_FIELD = '_numencode_translate_selector';

    public function init(): void
    {
        if (!plugin_exists('Winter.Translate')) {
            return;
        }

        // Lower priority than Translate's own listener (-1), so other plugins' field changes are included
        Event::listen('backend.form.extendFieldsBefore', function (Form $form) {
            if ($form instanceof RepeaterForm) {
                $this->registerItemTranslation($form);
            }
        }, -2);

        if (BackendOverride::enabled('assets')) {
            Event::listen('backend.form.extendFieldsBefore', function (Form $form) {
                $this->addLanguageSelector($form);
            });
        }

        // The same events on which Winter.Pages clears its caches (for the current locale only). The config switch is
        // read when the event fires, so it can be changed after the plugin booted.
        if (plugin_exists('Winter.Pages')) {
            $clear = function (): void {
                if (static::enabled('pages_cache_all_locales')) {
                    static::clearPagesCacheForAllLocales();
                }
            };

            Event::listen('pages.object.save', $clear);
            Event::listen('cms.template.save', $clear);
        }

        // Before Translate's own saveInternal listener (priority 0), which validates every changed language. Extend
        // callbacks of a parent class run for every subclass; the behavior is checked when the model is saved,
        // because behaviors are loaded after the extend callbacks.
        Model::extend(function (Model $model): void {
            $model->bindEvent('model.saveInternal', fn () => static::clearBlankTranslations($model), 100);
        });
    }

    /**
     * Empties translations that are only whitespace, so the language falls back to the default value.
     *
     * The backend rich editor beautifies an empty editor to "\n", and Winter.Translate stores it as the value of the
     * language the editor showed. Translate then validates that language with "\n" instead of the default value, so a
     * `required` rule fails ("The Content field is required."), and the website would show the blank value instead of
     * the default one.
     */
    public static function clearBlankTranslations(Model $model): void
    {
        if (!static::enabled('clear_blank_translations') || !$model->isClassExtendedWith(self::TRANSLATABLE_BEHAVIOR)) {
            return;
        }

        foreach ($model->getDirtyLocales() as $locale) {
            foreach ($model->getTranslateDirty($locale) as $key => $value) {
                if (is_string($value) && $value !== '' && trim($value) === '') {
                    $model->setAttributeTranslated($key, '', $locale);
                }
            }
        }
    }

    /**
     * Whether a Translate integration feature is enabled (config numencode.fundamentals::translate.<feature>).
     */
    public static function enabled(string $feature): bool
    {
        return (bool) Config::get('numencode.fundamentals::translate.' . $feature, true);
    }

    /**
     * Clears the Winter.Pages caches (routes, menus, snippet maps) of every enabled locale.
     *
     * Winter.Translate appends the current locale to the Pages cache keys, so Pages' own clearCache() only reaches
     * the caches of the locale it runs in (the backend's). The other locales would show stale URLs, menus and
     * snippets until the caches expire.
     */
    public static function clearPagesCacheForAllLocales(): void
    {
        $current = Lang::getLocale();

        try {
            foreach (array_keys(Locale::listEnabled()) as $locale) {
                Lang::setLocale($locale);
                PagesPlugin::clearCache();
            }
        } finally {
            Lang::setLocale($current);
        }
    }

    /**
     * Lets Translate turn the translatable fields of a relationrepeater item into multilingual fields.
     * The item form is bound to the related model, so its own translatable attributes are used.
     */
    protected function registerItemTranslation(RepeaterForm $form): void
    {
        $isNested = $form->isNested;
        $form->isNested = false;

        try {
            EventRegistry::instance()->registerModelTranslation($form);
        } finally {
            $form->isNested = $isNested;
        }
    }

    /**
     * Adds the language selector to top-level forms that contain translatable fields.
     */
    protected function addLanguageSelector(Form $form): void
    {
        if ($form->isNested || $form->getContext() === 'preview' || !$form->model instanceof Model) {
            return;
        }

        if (count(Locale::listEnabled()) < 2 || !$this->hasTranslatableFields($form->model)) {
            return;
        }

        // Prepended to the field definitions, so it is the first field of the form
        $form->fields = [
            self::SELECTOR_FIELD => [
                'type'     => 'partial',
                'path'     => '$/numencode/fundamentals/partials/_translate_selector.htm',
                'span'     => 'full',
                'cssClass' => 'translatable-selector-field',
            ],
        ] + (array) $form->fields;
    }

    /**
     * Checks whether the model, or a related model edited with relationrepeater, is translatable.
     */
    protected function hasTranslatableFields(Model $model): bool
    {
        if ($model->isClassExtendedWith(self::TRANSLATABLE_BEHAVIOR)) {
            return true;
        }

        if (!$model->isClassExtendedWith(RelationableModel::class)) {
            return false;
        }

        foreach ((array) ($model->relationable ?? []) as $relation) {
            if ($model->hasRelation($relation)
                && $model->$relation()->getRelated()->isClassExtendedWith(self::TRANSLATABLE_BEHAVIOR)) {
                return true;
            }
        }

        return false;
    }
}
