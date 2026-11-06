<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Behaviors;

use Db;
use Illuminate\Support\Collection;
use Lang;
use NumenCode\Fundamentals\Scopes\PublishableScope;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Traits\Sortable;
use Winter\Storm\Database\Traits\Validation;
use Winter\Storm\Exception\ValidationException;
use Winter\Storm\Extension\ExtensionBase;
use Winter\Storm\Support\Str;

/**
 * Lets a model edit its "has many" related records inline, with the relationrepeater form widget.
 *
 * Usage in the parent model:
 *
 *     public $implement = ['NumenCode.Fundamentals.Behaviors.RelationableModel'];
 *
 *     // form field name => relation name
 *     public $relationable = ['items_list' => 'items'];
 *
 *     public $hasMany = ['items' => [Item::class, 'key' => 'group_id', 'delete' => true]];
 *
 * and in fields.yaml:
 *
 *     items_list:
 *         type: relationrepeater
 *         form: $/acme/demo/models/item/fields.yaml
 *
 * On save, posted items with a known ID update that related record, items without one are created,
 * and related records missing from the posted items are deleted. Translations posted by
 * Winter.Translate (RLTranslate) are stored on the related records. The item order is stored when
 * the related model uses the Sortable trait.
 */
class RelationableModel extends ExtensionBase
{
    /**
     * Keys in the posted item data that are not model attributes.
     */
    protected const RESERVED_KEYS = ['RLTranslate', '_group'];

    protected Model $model;

    /**
     * Form field name => relation name.
     *
     * @var array<string, string>
     */
    protected array $relationable = [];

    /**
     * Posted item data per relation, waiting to be saved.
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    protected array $pendingData = [];

    /**
     * Related models prepared from the posted data (filled, not saved yet), per relation.
     *
     * @var array<string, array<int, Model>>
     */
    protected array $preparedModels = [];

    /**
     * Existing related records per relation, keyed by ID.
     *
     * @var array<string, Collection>
     */
    protected array $existing = [];

    public function __construct(Model $model)
    {
        $this->model = $model;

        $relationable = $model->relationable ?? ($model->getDynamicProperties()['relationable'] ?? []);
        $this->relationable = is_array($relationable) ? $relationable : [];

        foreach (array_keys($this->relationable) as $field) {
            $studly = Str::studly($field);

            $model->addDynamicMethod('get' . $studly . 'Attribute', fn () => $this->getRelationableValue($field));
            $model->addDynamicMethod('set' . $studly . 'Attribute', fn ($value) => $this->setRelationableValue($field, $value));
        }

        $model->bindEvent('model.afterValidate', fn () => $this->validateRelationable());
        $model->bindEvent('model.afterSave', fn () => $this->saveRelationable());
    }

    /**
     * Returns the relation name for a relationable form field, or null.
     */
    public function getRelationable(string $field): ?string
    {
        return $this->relationable[$field] ?? null;
    }

    /**
     * Returns the related model for a repeater item: the existing related record with the item's ID,
     * or a new instance. Used by the relationrepeater widget to give each item form its own model.
     *
     * @param array<string, mixed> $data
     */
    public function getRelationableModel(string $field, array $data = []): ?Model
    {
        if (!$relation = $this->getRelationable($field)) {
            return null;
        }

        $id = $data[$this->makeRelated($relation)->getKeyName()] ?? null;

        if ($id !== null && $id !== '' && $existing = $this->existingModels($relation)->get($id)) {
            return $existing;
        }

        return $this->makeRelated($relation);
    }

    /**
     * Getter of the relationable form field: the related records as arrays.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getRelationableValue(string $field): array
    {
        $relation = $this->getRelationable($field);

        return $relation ? $this->model->$relation->toArray() : [];
    }

    /**
     * Setter of the relationable form field: keeps the posted items until the parent model is saved.
     */
    protected function setRelationableValue(string $field, mixed $value): void
    {
        if (!$relation = $this->getRelationable($field)) {
            return;
        }

        $this->pendingData[$relation] = is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
        unset($this->preparedModels[$relation]);
    }

    /**
     * Validates the related models (when they use the Validation trait) together with the parent model.
     * Called after the parent model's own validation.
     */
    protected function validateRelationable(): void
    {
        foreach ($this->relationable as $field => $relation) {
            if (!isset($this->pendingData[$relation])) {
                continue;
            }

            foreach ($this->prepareModels($relation) as $index => $related) {
                if (!in_array(Validation::class, class_uses_recursive($related))) {
                    continue;
                }

                // validate() throws by default, but returns false when throwOnValidation is disabled
                try {
                    $valid = $related->validate();
                } catch (ValidationException $e) {
                    $valid = false;
                }

                if ($valid) {
                    continue;
                }

                throw new ValidationException([
                    $field => Lang::get('numencode.fundamentals::lang.relationable.item_error', [
                        'index'   => $index + 1,
                        'message' => $related->errors()->first(),
                    ]),
                ]);
            }

            // Optional custom validation in the parent model: validate{Field}(array $items): ?string
            $method = 'validate' . Str::studly($field);

            if ($this->model->methodExists($method) && $error = $this->model->$method($this->pendingData[$relation])) {
                throw new ValidationException([$field => $error]);
            }
        }
    }

    /**
     * Saves the pending related records. Runs after the parent model was saved, in a transaction.
     */
    public function saveRelationable(): void
    {
        if (!$this->pendingData) {
            return;
        }

        Db::transaction(function () {
            foreach (array_keys($this->pendingData) as $relation) {
                $this->saveRelation($relation);
            }
        });

        $this->pendingData = [];
        $this->preparedModels = [];
    }

    protected function saveRelation(string $relation): void
    {
        $existing = $this->existingModels($relation);
        $keptIds = [];

        foreach ($this->prepareModels($relation) as $related) {
            if ($related->exists) {
                $related->save();
            } else {
                $this->model->$relation()->save($related);
            }

            $keptIds[] = $related->getKey();
        }

        // Delete the related records that were removed from the repeater
        $existing->except($keptIds)->each(fn (Model $related) => $related->delete());

        $this->model->unsetRelation($relation);
        unset($this->existing[$relation]);
    }

    /**
     * Builds the related models from the posted data (filled, with translations and order, not saved).
     *
     * @return array<int, Model>
     */
    protected function prepareModels(string $relation): array
    {
        if (isset($this->preparedModels[$relation])) {
            return $this->preparedModels[$relation];
        }

        $existing = $this->existingModels($relation);
        $models = [];

        foreach ($this->pendingData[$relation] ?? [] as $order => $data) {
            $related = $this->makeRelated($relation);
            $keyName = $related->getKeyName();
            $id = $data[$keyName] ?? null;

            // Only records that already belong to this parent can be updated; anything else is created
            if ($id !== null && $id !== '' && $existing->has($id)) {
                $related = $existing->get($id);
            }

            $related->fill(array_diff_key($data, array_flip([...self::RESERVED_KEYS, $keyName])));

            if (isset($data['RLTranslate']) && is_array($data['RLTranslate'])) {
                $this->setTranslations($related, $data['RLTranslate']);
            }

            if (in_array(Sortable::class, class_uses_recursive($related))) {
                $related->{$related->getSortOrderColumn()} = $order + 1;
            }

            $models[] = $related;
        }

        return $this->preparedModels[$relation] = $models;
    }

    /**
     * Returns the existing related records keyed by ID, including unpublished ones.
     */
    protected function existingModels(string $relation): Collection
    {
        if (!$this->model->exists) {
            return new Collection();
        }

        return $this->existing[$relation] ??= $this->model->$relation()
            ->withoutGlobalScope(PublishableScope::class)
            ->get()
            ->keyBy(fn (Model $related) => $related->getKey());
    }

    protected function makeRelated(string $relation): Model
    {
        return $this->model->$relation()->getRelated()->newInstance();
    }

    /**
     * Stores the translations posted by Winter.Translate on a related model.
     *
     * @param array<string, mixed> $translations locale => [attribute => value]
     */
    protected function setTranslations(Model $related, array $translations): void
    {
        if (!$related->isClassExtendedWith('Winter.Translate.Behaviors.TranslatableModel')) {
            return;
        }

        $translatable = $related->getTranslatableAttributes();

        foreach ($translations as $locale => $values) {
            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $attribute => $value) {
                if (in_array($attribute, $translatable, true)) {
                    $related->setAttributeTranslated($attribute, $value, (string) $locale);
                }
            }
        }
    }
}
