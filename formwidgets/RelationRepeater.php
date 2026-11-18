<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\FormWidgets;

use Backend\FormWidgets\Repeater;
use Lang;
use NumenCode\Fundamentals\Behaviors\RelationableModel;
use NumenCode\Fundamentals\Classes\RepeaterForm;
use Request;
use Winter\Storm\Exception\ApplicationException;
use Winter\Storm\Html\Helper as HtmlHelper;

/**
 * A repeater that edits a model's "has many" related records (form widget type "relationrepeater").
 *
 * It behaves like the core repeater (all its options work: titleFrom, style, minItems, maxItems,
 * sortable, ...), but each item form is bound to its own related model, so related-model features
 * (Winter.Translate fields, file uploads, ...) work inside the items. The parent model must implement
 * the RelationableModel behavior; see that class for an example.
 *
 * Extra options:
 *
 *     showToggleAll: true   # "Expand all" / "Collapse all" buttons above the items (list mode, not with style: accordion)
 *     itemActions: true     # copy, duplicate and publish/unpublish buttons on each item, and "Paste item"
 *     publishField: is_published   # the item field the publish button toggles (default; no button without it)
 *
 * Item actions: a duplicate is rendered by the server from the item's posted values (onDuplicateItem) without its
 * key, so saving creates a new record (with the posted translations). A copied item is kept in the browser's local
 * storage and can be pasted into any relation repeater with the same item form, e.g. in another record.
 */
class RelationRepeater extends Repeater
{
    protected const PATH = '$/numencode/fundamentals/formwidgets/relationrepeater';

    /**
     * Show the "Expand all" and "Collapse all" buttons.
     */
    public bool $showToggleAll = false;

    /**
     * Show the copy, duplicate and publish buttons on each item, and "Paste item".
     */
    public bool $itemActions = false;

    /**
     * The item field toggled by the publish button.
     */
    public string $publishField = 'is_published';

    public function init()
    {
        $this->showToggleAll = (bool) $this->getConfig('showToggleAll', false);
        $this->itemActions = (bool) $this->getConfig('itemActions', false);
        $this->publishField = (string) $this->getConfig('publishField', 'is_published');

        parent::init();
    }

    public function render()
    {
        $html = parent::render();

        if (!$this->hasToggleAll() && !$this->hasItemActions()) {
            return $html;
        }

        return $this->makePartial(static::PATH . '/partials/_relationrepeater.php', [
            'repeater'    => $html,
            'toggleAll'   => $this->hasToggleAll(),
            'itemActions' => $this->hasItemActions() ? $this->itemActionsConfig() : null,
        ]);
    }

    /**
     * Whether the item actions are shown: enabled, in list mode and not in preview mode.
     */
    public function hasItemActions(): bool
    {
        return $this->itemActions && !$this->previewMode && ($this->vars['mode'] ?? $this->mode) === 'list';
    }

    /**
     * The settings of the item actions for relationrepeater.js.
     *
     * @return array<string, mixed>
     */
    public function itemActionsConfig(): array
    {
        $fields = (array) ($this->makeConfig($this->form)->fields ?? []);

        return [
            'handler'      => $this->getEventHandler('onDuplicateItem'),
            'field'        => $this->getFieldName(),
            'formKey'      => $this->formKey(),
            'publishField' => isset($fields[$this->publishField]) ? $this->publishField : null,
            'labels'       => Lang::get('numencode.fundamentals::lang.repeater.actions'),
        ];
    }

    /**
     * Identifies the item form, so a copied item is only pasted into repeaters with the same item form.
     */
    public function formKey(): string
    {
        return md5(is_string($this->form) ? $this->form : (string) json_encode($this->form));
    }

    /**
     * AJAX: renders a new item filled with values – a duplicate of the posted item at _repeater_source, or a copied
     * item (_repeater_paste, JSON from the browser, with _repeater_paste_form = its form key). The record key is left
     * out, so the item is saved as a new record.
     *
     * @return array<string, string> The item's markup ("item") and its index ("index")
     * @throws ApplicationException
     */
    public function onDuplicateItem(): array
    {
        $this->abortIfPreviewMode();

        $items = post($this->getFieldName());
        $items = is_array($items) ? $items : [];

        if (($paste = post('_repeater_paste')) !== null) {
            $copy = is_string($paste) ? json_decode($paste, true) : null;

            if (!is_array($copy) || post('_repeater_paste_form') !== $this->formKey()) {
                throw new ApplicationException(Lang::get('numencode.fundamentals::lang.repeater.actions.paste_unavailable'));
            }
        } else {
            $source = post('_repeater_source');
            $copy = is_scalar($source) ? ($items[$source] ?? null) : null;

            if (!is_array($copy)) {
                throw new ApplicationException(Lang::get('numencode.fundamentals::lang.repeater.actions.duplicate_failed'));
            }
        }

        if ($this->maxItems && count($items) >= $this->maxItems) {
            throw new ApplicationException(Lang::get('numencode.fundamentals::lang.repeater.actions.duplicate_max'));
        }

        unset($copy[$this->getItemModel([])->getKeyName()]);

        $index = $this->getNextIndex();

        // The new item's form reads its values from the posted field at its index
        $parts = HtmlHelper::nameToArray($this->getFieldName());
        $root = array_shift($parts);
        $value = Request::post($root);
        $value = is_array($value) ? $value : [];
        array_set($value, implode('.', array_merge($parts, [(string) $index])), $copy);
        Request::merge([$root => $value]);

        $this->prepareVars();
        $this->vars['widget'] = $this->makeItemFormWidget($index, $copy['_group'] ?? null);
        $this->vars['indexValue'] = $index;

        return ['item' => $this->makePartial('repeater_item'), 'index' => (string) $index];
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

        // Called before init(), so the options are read from the config
        if ($this->getConfig('showToggleAll', false) || $this->getConfig('itemActions', false)) {
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

        $model = $this->model->getRelationableModel($this->formField->getName(false), $data) ?? $this->model;

        // A new item (e.g. a duplicate) shows its posted translations: Translate's fields read them from the model
        if ($model !== $this->model && !$model->exists && is_array($data['RLTranslate'] ?? null) && $model->methodExists('setAttributeTranslated')) {
            foreach ($data['RLTranslate'] as $locale => $attributes) {
                foreach (is_array($attributes) ? $attributes : [] as $attribute => $value) {
                    if (is_string($attribute) && is_scalar($value)) {
                        $model->setAttributeTranslated($attribute, (string) $value, (string) $locale);
                    }
                }
            }
        }

        return $model;
    }
}
