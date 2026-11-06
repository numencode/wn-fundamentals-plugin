<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Classes;

use Backend\Classes\FormField;

/**
 * A form field inside a relationrepeater item.
 *
 * Winter.Translate names the locale inputs of a field "RLTranslate[locale][field]" at the top level of the
 * form, so all items would share the same names. Inside a repeater item, they are named
 * "parent[items][0][RLTranslate][locale][field]" instead, so the translations are posted with the item and
 * the RelationableModel behavior can store them on the related record.
 *
 * @internal Used by the relationrepeater form widget.
 */
class RepeaterFormField extends FormField
{
    protected const TRANSLATE_PREFIX = 'RLTranslate[';

    /**
     * Creates a RepeaterFormField with the full state of an existing form field.
     */
    public static function fromField(FormField $field): static
    {
        $instance = new static($field->fieldName, $field->label);

        // Called in the scope of a FormField subclass, so protected properties are copied too
        foreach (get_object_vars($field) as $property => $value) {
            $instance->$property = $value;
        }

        return $instance;
    }

    public function getName($arrayName = null)
    {
        if (is_string($arrayName) && str_starts_with($arrayName, self::TRANSLATE_PREFIX) && $this->arrayName) {
            $arrayName = $this->arrayName . '[RLTranslate]' . substr($arrayName, strlen('RLTranslate'));
        }

        return parent::getName($arrayName);
    }
}
