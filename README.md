# Fundamentals plugin

This plugin contains some fundamental functionalities that facilitate application development.

## Backend overrides

A backend dashboard override system is incorporated in the plugin and includes a user-friendly "Settings" page.

## Common translations

Fundamentals plugin includes some common language translations which can be used elsewhere.

## Helper functions

Some helper functions are included in the plugin and can be used elsewhere in the application.

| Function | Description |
| --- | --- |
| numencode_partial         | Returns the path to the NumenCode partial file. |
| validate_request          | Validates current request and flashes errors to the session. Returns true if the request is valid or false if it's not. |
| select_options            | Creates options for the select element. |
| array_insert              | Inserts a new element into a specific position inside an array. |
| array_move_element_before | Moves a specific array element before another array element in an associated array by halving the array at the desired position and squeezing the element-to-be-moved into the gap created. |
| array_merge_reference     | Merges elements from passed arrays into one array and keeps a reference to the original arrays. |
| array_search_recursive    | Searches the array recursively for a given value and returns the corresponding keys if successful. |
| round_global              | Rounds the number to a number of decimals defined in a global setting. |
| plugin_exists             | Checks if plugin exists and is enabled. |
| extend_class              | Extends a class with a behavior. |
| dumpbug                   | Dumps a simple debug backtrace. |
| diebug                    | Dumps a simple debug backtrace and ends a script. |
| dd_query                  | Dumps the next database query. |
| d                         | Dumps the passed variables and does not end the script. |
| ddd                       | Quick fix for not rendering dd() in the browser's network tab. |
| ddt                       | Dumps a simple debug backtrace and ends the script. Useful for console debugging. |

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


## Traits

### Progress bar

Progress bar can be used to display the progress status in the CLI while iterating through an array,
when running a certain console command.

### Publishable

A publishable trait can be used on models that allow content to be published or hidden from the website.
Model entity must use the Publishable trait, and the table must include boolean field `is_published`.

**Usage in Model**

    class Acme extends Model
    {
        use \NumenCode\Fundamentals\Traits\Publishable;

        ...
    }

## Behaviours

### RelationableModel Behavior

RelationableModel behavior enables `repeater` to be used as relations editor via relation behavior.

For the purpose of demonstration, let's say we created two models: `Category` and `Item`.
`Category` can have multiple items which we want to display and edit via the repeater.

`Category` model must:
 - implement the `RelationableModel` behavior
 - define the `$hasMany` relationship for the items
 - define the `$relationable` property (where key is a relationable property name and value is the relationship)

Here's the mockup for the `Category` model:

    class Category extends Model
    {
        public $implement = [
            '@NumenCode.Fundamentals.Behaviors.RelationableModel',
        ];

        public $hasMany = [
            'items' => [Item::class, 'key' => 'category_id'],
        ];

        public $relationable = [
            'items_list' => 'items',
        ];
    }

Finally, `repeater` for the items must be defined in `\models\category\fields.yaml` as such:

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

# Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

# Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

# Security

If you discover any security-related issues, please email info@numencode.com instead of using the issue tracker.

# Author

**NumenCode.Fundamentals** plugin was created by and is maintained by [Blaz Orazem](https://www.orazem.si/).

Please write an email to info@numencode.com about all the things concerning this project.

Follow [@blazorazem](https://twitter.com/blazorazem) on Twitter.

# License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

[![MIT License](https://img.shields.io/github/license/numencode/fundamentals-plugin?label=License&color=blue&style=flat-square&cacheSeconds=600)](https://github.com/numencode/fundamentals-plugin/blob/main/LICENSE.md)
