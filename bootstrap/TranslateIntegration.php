<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Bootstrap;

use Backend\Widgets\Form;
use Event;
use NumenCode\Fundamentals\Behaviors\RelationableModel;
use NumenCode\Fundamentals\Classes\RepeaterForm;
use Winter\Storm\Database\Model;
use Winter\Translate\Classes\EventRegistry;
use Winter\Translate\Models\Locale;

/**
 * Winter.Translate support (only active when the Translate plugin is installed and enabled):
 *
 * - Translatable fields inside relationrepeater items become multilingual (Translate itself skips
 *   nested forms).
 * - Backend forms with translatable fields get a language selector at the top, which switches all
 *   fields of the form at once and can copy all translations from another language.
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
