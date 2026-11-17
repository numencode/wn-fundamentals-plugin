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

        // The same events on which Winter.Pages clears its caches (for the current locale only)
        if (static::enabled('pages_cache_all_locales') && plugin_exists('Winter.Pages')) {
            Event::listen('pages.object.save', fn () => static::clearPagesCacheForAllLocales());
            Event::listen('cms.template.save', fn () => static::clearPagesCacheForAllLocales());
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
