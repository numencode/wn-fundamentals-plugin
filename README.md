# Fundamentals plugin

This plugin contains some fundamental functionalities that facilitate application development.

## Backend overrides

A backend dashboard override system is incorporated in the plugin and includes a user-friendly "Settings" page.

## Common translations

Fundamentals plugin includes some common language translations which can be used elsewhere.

## CMS permissions

CmsPermissions class can be used to allow or revoke specific user groups from running certain actions
such as creating, updating and deleting data.

**1. First you must use it in the Plugin's boot method**

    use NumenCode\Fundamentals\Classes\CmsPermissions;

    class Plugin extends PluginBase
    {
        public function boot()
        {
            CmsPermissions::revokeDelete('owners', AcmeController::class);
            CmsPermissions::revokeUpdate(['owners', 'publishers'], CustomController::class);
        }

        ...
    }

**2. Then you can use it in eg. template**

    <?php if(\NumenCode\Fundamentals\Classes\CmsPermissions::canDelete($controller)): ?>
        <button
            type="button"
            class="oc-icon-trash-o btn-icon danger pull-right"
            data-request="onDelete"
            data-load-indicator="<?= e(trans('backend::lang.form.deleting')) ?>"
            data-request-confirm="<?= e(trans('backend::lang.form.confirm_delete')) ?>">
        </button>
    <?php endif; ?>


## Publishable

Publishable trait can be used on models that allow content to be published or hidden from the website.
Model entity must use the Publishable trait and the table must include boolean field `is_published`.

**Usage in Model**

    class Acme extends Model
    {
        use \NumenCode\Fundamentals\Traits\Publishable;

        ...
    }

## Relationable

Relationable trait enables `repeater` to be used as relations editor via Relation behavior.

For the purpose of demonstration, let's say we created two models: `Category` and `Item`.
`Category` can have multiple items which we want to display and edit via the repeater.

`Category` model must use `Relationable` trait and it needs two defined properties:
`$hasMany` and `$relationable`.

Here's the mockup for the `Category` model:

    class Category extends Model
    {
        use \NumenCode\Fundamentals\Traits\Relationable;

        ...

        public $hasMany = [
            'items' => [Item::class, 'key' => 'category_id'],
        ];

        public $relationable = [
            'items_list' => 'items',
        ];

        ...
    }

Finally, `repeater` for items must be defined in `\models\category\fields.yaml` as such:

    fields:
        ...
        items_list:
            prompt: Add new item
            span: full
            type: repeater
            cssClass: 'repeater-collapsible repeater-open'
            form: $/models/item/fields.yaml
        ...

## Twig extensions

Multiple Twig extensions are available in order to provide a better development experience.
Extensions are divided into two scopes, filters and functions, which can all be used across the Twig template files.

### Filters

| Command | Description | Usage |
| --- | --- | --- |
| resize | Resize image and create a thumbnail cache file | 'picture.jpg'&#124;media&#124;resize('600x400.crop') |
| url_path | Parse a URL and return its components | 'file.pdf'&#124;url_path |

### Functions

| Command | Description | Usage |
| --- | --- | --- |
| app            | TBD | TBD |
| asset_hash     | TBD | TBD |
| class_basename | TBD | TBD |
| collect        | TBD | TBD |
| config         | TBD | TBD |
| d              | TBD | TBD |
| dd             | TBD | TBD |
| detect         | TBD | TBD |
| device_type    | TBD | TBD |
| require        | TBD | TBD |
| trans          | TBD | TBD |
| trim           | TBD | TBD |
| url_params     | TBD | TBD |
