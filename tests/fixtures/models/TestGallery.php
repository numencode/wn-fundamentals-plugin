<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\Fixtures\Models;

use NumenCode\Fundamentals\Behaviors\RelationableModel;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Traits\Validation;

/**
 * Parent model for the tests: edits its items with the RelationableModel behavior.
 */
class TestGallery extends Model
{
    use Validation;

    public $table = 'nc_test_galleries';

    public $implement = [
        '@Winter.Translate.Behaviors.TranslatableModel',
        RelationableModel::class,
    ];

    public $translatable = ['title'];

    public $rules = ['title' => 'required'];

    protected $fillable = ['title', 'is_published'];

    public $relationable = ['items_list' => 'items'];

    public $hasMany = [
        'items' => [TestItem::class, 'key' => 'gallery_id', 'order' => 'sort_order'],
    ];

    /**
     * Custom validation of all items (see RelationableModel).
     */
    public function validateItemsList(array $items): ?string
    {
        return count($items) > 5 ? 'Too many items.' : null;
    }
}
