<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Classes;

use Backend\Widgets\Form;

/**
 * The item form of the relationrepeater widget: a core form whose fields are RepeaterFormField instances,
 * so Winter.Translate values are posted per item.
 *
 * @internal Used by the relationrepeater form widget.
 */
class RepeaterForm extends Form
{
    /**
     * Use the core form's partials.
     */
    public function guessViewPath(string $suffix = '', bool $isPublic = false): ?string
    {
        return $this->guessViewPathFrom(Form::class, $suffix, $isPublic);
    }

    public function guessConfigPath($suffix = '')
    {
        return $this->guessConfigPathFrom(Form::class, $suffix);
    }

    protected function makeFormField($name, $config = [])
    {
        return RepeaterFormField::fromField(parent::makeFormField($name, $config));
    }

    /**
     * The core repeater calls this for every item when the parent form is saved.
     *
     * Winter.Translate's multilingual widgets are left out of the widget processing: their getSaveValue()
     * reads the translations from the top level of the request (the parent form's fields) and writes them to
     * the item's model. Inside relation repeater items, the translations are posted per item instead and
     * stored by the RelationableModel behavior, so these widgets just keep their posted (default locale) value.
     */
    public function getSaveData(): array
    {
        $widgets = $this->formWidgets;

        $this->formWidgets = array_filter($widgets, fn ($widget) => !static::isTranslateWidget($widget));

        try {
            return parent::getSaveData();
        } finally {
            $this->formWidgets = $widgets;
        }
    }

    /**
     * Checks whether a form widget is one of Winter.Translate's multilingual widgets (mltext, mlricheditor, ...).
     */
    public static function isTranslateWidget(object $widget): bool
    {
        return trait_exists(\Winter\Translate\Traits\MLControl::class)
            && in_array(\Winter\Translate\Traits\MLControl::class, class_uses_recursive($widget), true);
    }
}
