<?php

declare(strict_types=1);

namespace NumenCode\Fundamentals\Tests\Fixtures\Models;

use NumenCode\Fundamentals\Traits\Publishable;
use Winter\Storm\Database\Model;
use Winter\Storm\Database\Traits\Sortable;
use Winter\Storm\Database\Traits\Validation;

/**
 * Related model for the tests: translatable, sortable, publishable and validated.
 */
class TestItem extends Model
{
    use Publishable;
    use Sortable;
    use Validation;

    public $table = 'nc_test_items';

    public $implement = ['@Winter.Translate.Behaviors.TranslatableModel'];

    public $translatable = ['title', 'subtitle'];

    public $rules = ['title' => 'required'];

    protected $fillable = ['title', 'subtitle', 'is_published'];
}
