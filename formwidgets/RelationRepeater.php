<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\FormWidgets;

use Backend\FormWidgets\Repeater;
use NumenCode\Fundamentals\Behaviors\RelationableModel;
use NumenCode\Fundamentals\Classes\RepeaterForm;

/**
 * A repeater that edits a model's "has many" related records (form widget type "relationrepeater").
 *
 * It behaves like the core repeater (all its options work: titleFrom, style, minItems, maxItems,
 * sortable, ...), but each item form is bound to its own related model, so related-model features
 * (Winter.Translate fields, file uploads, ...) work inside the items. The parent model must implement
 * the RelationableModel behavior; see that class for an example.
 *
 * Extra option:
 *
 *     showToggleAll: true   # "Expand all" / "Collapse all" buttons above the items (list mode, not with style: accordion)
 */
class RelationRepeater extends Repeater
{
    protected const PATH = '$/numencode/fundamentals/formwidgets/relationrepeater';

    /**
     * Show the "Expand all" and "Collapse all" buttons.
     */
    public bool $showToggleAll = false;

    public function init()
    {
        $this->showToggleAll = (bool) $this->getConfig('showToggleAll', false);

        parent::init();
    }

    public function render()
    {
        $html = parent::render();

        if (!$this->hasToggleAll()) {
            return $html;
        }

        return $this->makePartial(static::PATH . '/partials/_toggle_all.php', ['repeater' => $html]);
    }

    /**
     * Whether the toggle buttons are shown: enabled, and the items can be collapsed one by one.
     */
    public function hasToggleAll(): bool
    {
        return $this->showToggleAll
            && ($this->vars['mode'] ?? $this->mode) === 'list'
            && ($this->vars['style'] ?? $this->style) !== 'accordion';
    }

    protected function loadAssets()
    {
        parent::loadAssets();

        // Called before init(), so the option is read from the config
        if ($this->getConfig('showToggleAll', false)) {
            $this->addCss(static::PATH . '/assets/css/relationrepeater.css', 'NumenCode.Fundamentals');
            $this->addJs(static::PATH . '/assets/js/relationrepeater.js', 'NumenCode.Fundamentals');
        }
    }

    /**
     * Use the core repeater's partials and assets.
     */
    public function guessViewPath(string $suffix = '', bool $isPublic = false): ?string
    {
        return $this->guessViewPathFrom(Repeater::class, $suffix, $isPublic);
    }

    public function guessConfigPath($suffix = '')
    {
        return $this->guessConfigPathFrom(Repeater::class, $suffix);
    }

    /**
     * Same as the core repeater (Winter 1.2.15), except that the item form is a RepeaterForm
     * bound to the related model of the item.
     */
    protected function makeItemFormWidget($index = 0, $groupCode = null)
    {
        $configDefinition = $this->useGroups
            ? $this->getGroupFormFieldConfig($groupCode)
            : $this->form;

        $data = $this->getValueFromIndex($index);

        $config = $this->makeConfig($configDefinition);
        $config->model = $this->getItemModel(is_array($data) ? $data : []);
        $config->data = $data;
        $config->alias = $this->alias . 'Form' . $index;
        $config->arrayName = $this->getFieldName() . '[' . $index . ']';
        $config->isNested = true;

        if (self::$onAddItemCalled || $this->minItems > 0) {
            $config->enableDefaults = true;
        }

        $widget = $this->makeWidget(RepeaterForm::class, $config);
        $widget->previewMode = $this->previewMode;
        $widget->bindToController();

        $this->indexMeta[$index] = [
            'groupCode' => $groupCode,
        ];

        return $this->formWidgets[$index] = $widget;
    }

    /**
     * The core repeater replaces each item's data with its form's getSaveData(), which only contains form
     * fields. The translations posted for the item (RLTranslate) are added back, so the RelationableModel
     * behavior can store them on the related record.
     */
    protected function processSaveValue($value)
    {
        $result = parent::processSaveValue($value);

        if (!is_array($result) || !is_array($value)) {
            return $result;
        }

        // The core repeater keeps the posted order and re-indexes the items with array_values()
        $posted = array_values($value);

        foreach ($result as $index => $item) {
            if (is_array($item) && isset($posted[$index]['RLTranslate']) && is_array($posted[$index]['RLTranslate'])) {
                $result[$index]['RLTranslate'] = $posted[$index]['RLTranslate'];
            }
        }

        return $result;
    }

    /**
     * Returns the related model of an item, or the parent model when it doesn't implement RelationableModel.
     *
     * @param array<string, mixed> $data
     */
    protected function getItemModel(array $data)
    {
        if (!$this->model->isClassExtendedWith(RelationableModel::class)) {
            return $this->model;
        }

        return $this->model->getRelationableModel($this->formField->getName(false), $data) ?? $this->model;
    }
}
