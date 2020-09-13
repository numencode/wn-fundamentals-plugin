# Fundamentals plugin

This plugin contains some fundamental functionalities that facilitate application development.

## Backend overrides

A backend dashboard override system is incorporated in the plugin and includes a user-friendly "Settings" page.

## Common translations

Fundamentals plugin includes some common language translations which can be used elsewhere.

## CMS permissions

CmsPermissions class can be used to allow or revoke specific user groups from running certain actions
such as creating, updating and deleting data.

**1. Permission must be configured in the Plugin's boot() method**

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

**2. After you can use it in a template for an example**

    <?php if(\NumenCode\Fundamentals\Classes\CmsPermissions::canDelete($controller)): ?>
        <button
            type="button"
            class="oc-icon-trash-o btn-icon danger pull-right"
            data-request="onDelete"
            data-load-indicator="<?= e(trans('backend::lang.form.deleting')) ?>"
            data-request-confirm="<?= e(trans('backend::lang.form.confirm_delete')) ?>">
        </button>
    <?php endif; ?>


## Publishable Trait

Publishable trait can be used on models that allow content to be published or hidden from the website.
Model entity must use the Publishable trait and the table must include boolean field `is_published`.

**Usage in Model**

    class Acme extends Model
    {
        use \NumenCode\Fundamentals\Traits\Publishable;

        ...
    }

## Relationable Behavior

Relationable behavior enables `repeater` to be used as relations editor via Relation behavior.

For the purpose of demonstration, let's say we created two models: `Category` and `Item`.
`Category` can have multiple items which we want to display and edit via the repeater.

`Category` model must:
 - implement `Relationable` behavior
 - define `$hasMany` property (for items)
 - extend a model with a dynamic property `$relationable`

Here's the mockup for the `Category` model:

    class Category extends Model
    {
        public $implement = [
            '@NumenCode.Fundamentals.Behaviors.Relationable',
        ];

        public $hasMany = [
            'items' => [Item::class, 'key' => 'category_id'],
        ];

        public static function boot()
        {
            parent::boot();

            static::extend(function ($model) {
                $model->addDynamicProperty('relationable', ['items_list' => 'items']);
            });
        }
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
| resize   | Resize an image and create a thumbnail cache file | 'picture.jpg'&#124;media&#124;resize('600x400.crop') |
| str_pad  | Pad a string to a certain length with another string on the left side | 'file.pdf'&#124;str_pad(4, '0') |
| url_path | Parse a URL and return its components | 'file.pdf'&#124;url_path |

### Functions

| Command | Description | Usage |
| --- | --- | --- |
| app            | Get the available container instance | app() |
| asset_hash     | Store the unique value in the cache forever which can be used as an asset version | asset_hash() |
| class_basename | Get the class "basename" of the given object / class | class_basename('\\Acme\\SomeClassName') |
| collect        | Creates a collection from the given value | collect($someStringOrArray) |
| config         | Get / set the specified configuration value | config('app.some_setting') |
| d              | Dumps the passed variables and does not end the script | d($variable) |
| dd             | Dumps the passed variables and ends the script | dd($variable) |
| detect         | Detect mobile devices (including tablets) | dd(detect()) |
| device_type    | Returns either 'mobile' or 'desktop' code, based on a detected device | device_type() |
| require        | Uses file_get_contents() to read the entire file into a string | require('filename.txt') |
| trans          | Translate the given string | trans('Some random sentence') |
| trim           | Strip whitespace (or other characters) from the beginning and end of a string | trim(' Some random sentence ') |
| url_params     | Returns the current routing parameters | dd(url_params()) |
