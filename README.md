# Fundamentals Plugin

The **Fundamentals Plugin** provides essential functionalities to streamline application development within
the Winter CMS ecosystem.  It includes backend overrides, helper functions, Twig extensions, and more,
allowing developers to build robust applications efficiently.

[![Version](https://img.shields.io/github/v/release/numencode/wn-fundamentals-plugin?style=flat-square&color=0099FF)](https://github.com/numencode/wn-fundamentals-plugin/releases)
[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/numencode/wn-fundamentals-plugin?style=flat-square&color=0099FF)](https://packagist.org/packages/numencode/wn-fundamentals-plugin)
[![Checks](https://img.shields.io/github/check-runs/numencode/wn-fundamentals-plugin/main?style=flat-square)](https://github.com/numencode/wn-fundamentals-plugin/actions)
[![Tests](https://img.shields.io/github/actions/workflow/status/numencode/wn-fundamentals-plugin/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/numencode/wn-fundamentals-plugin/actions)
[![License](https://img.shields.io/github/license/numencode/wn-fundamentals-plugin?label=open%20source&style=flat-square&color=0099FF)](https://github.com/numencode/wn-fundamentals-plugin/blob/main/LICENSE.md)

---

## Target Audience

This plugin is designed for developers working with Winter CMS who seek to accelerate development processes
and enhance code maintainability by leveraging reusable components and utilities.

## Dependencies

This plugin is a prerequisite for other `NumenCode` Winter CMS plugins. To use those plugins, this one must be
installed first as it provides essential components and utilities required for their functionality.

## Installation

This plugin is available for installation via [Composer](http://getcomposer.org/).

```bash
composer require numencode/wn-fundamentals-plugin
```

After installing the plugin you will need to run the migrations.

```bash
php artisan winter:up
```

## Requirements

* [Winter CMS](https://wintercms.com/) 1.2.7 or higher.
* PHP version 8.0 or higher.

---

## Features Overview

## Common Translations

The Fundamentals plugin provides a comprehensive set of common language translations designed for reuse across applications.
These translations promote consistency, streamline development, and eliminate the need for repetitive localization efforts.

Additionally, the common translations are fully compatible with and intended for use alongside other NumenCode plugins.
Since the Fundamentals plugin is a prerequisite for all NumenCode plugins, these translations serve as a standardized
foundation, ensuring seamless integration and uniformity across projects utilizing the NumenCode ecosystem.

---

## Backend Overrides

The `BackendOverride` class customizes and extends the behavior of the Winter CMS backend panel.
It provides a centralized way to enhance the backend's functionality, styling, and user experience.
This class is useful for injecting custom styles, scripts, or modifying the backend behavior without
altering the core framework.

### Key Features

- **Custom Styles and Scripts:**
  - Automatically adds custom SCSS and JavaScript files to backend pages.
  - Combines and serves these assets for improved performance.
- **Enhanced Settings Page:**
  - Modifies the behavior of the Settings page (System\Controllers\Settings) and displays a customized layout.
- **Custom List Column Rendering:**
  - Overrides the value rendering for list columns of type switch to provide a visually enhanced representation
  (icons and colors).

---

## Config Overrides

The `ConfigOverride` class enables developers to customize and extend configuration files dynamically across a
Winter CMS application. Its primary purpose is to facilitate granular or global overrides of configuration files,
reducing duplication and enhancing flexibility.

### Key Features

- **Field Customization**: Modify or extend `fields.yaml` files for any class using the `extendFields()` method.
- **Column Customization**: Adjust or add columns to `columns.yaml`, `columns_import.yaml`, or `columns_export.yaml` via dedicated methods like `extendColumns()`, `extendImportColumns()`, and `extendExportColumns()`.
- **Import/Export Enhancements**: Extend configurations for `config_import_export.yaml` using `extendImportExport()`.
- **Global Overrides**: Apply global overrides to all configuration files using the `extendAll()` method, ensuring system-wide customizations.
- **Scoped Overrides**: Limit overrides to specific classes and configuration files using methods like `extend()` for precise control.
- **Pages Plugin Integration**: Automatically aligns form tabs and secondary tabs for the Winter Pages plugin to improve backend usability.

### Usage Example

```php
use NumenCode\Fundamentals\Bootstrap\ConfigOverride;

// Extend fields.yaml for a specific model
ConfigOverride::extendFields(ExampleModel::class, function ($fields) {
    $fields['new_field'] = [
        'label'   => 'New Field',
        'type'    => 'text',
        'default' => 'Default Value',
    ];

    return $fields;
});

// Extend columns.yaml for a list view
ConfigOverride::extendColumns(ExampleModel::class, function ($columns) {
    $columns['new_column'] = [
        'label' => 'New Column',
        'type'  => 'text',
    ];

    return $columns;
});

// Global Overrides: Apply global configuration changes across the system.
ConfigOverride::extendAll(function ($filePath, $config) {
    if ($filePath === 'backend/models/user/fields.yaml') {
        $config['tabs']['fields']['new_field'] = [
            'label' => 'Global New Field',
            'type'  => 'text',
        ];
    }

    return $config;
});

// Integration with Winter Pages Plugin: Automatically align form tabs for Pages
Event::listen('backend.form.extendFieldsBefore', function ($form) {
    if (get_class($form->model) === Winter\Pages\Classes\Page::class) {
        // Adjust fields in the secondary tab
        foreach ((array) $form->secondaryTabs['fields'] as $key => $value) {
            $value['cssClass'] = trim(str_replace('secondary-tab', '', $value['cssClass']));
            unset($form->secondaryTabs['fields'][$key]);
            $form->tabs['fields'][$key] = $value;
        }
    }
}, 1000);
```

---

## Form Widgets Overrides

### Repeater Field Type

The `Repeater` field type allows you to display and manage a form with multiple collapsible sections,
each representing an individual record. This is useful for managing lists of related data, where each
item can be edited using the same form structure.

#### Usage Example

Define a `Repeater` field in your form configuration YAML:

```yaml
fields:
    posts:
        label: Posts
        type: repeater
        span: auto
        form:
            fields:
                id:
                    label: ID
                    type: number
                    cssClass: hidden
                title:
                    label: Title
                    type: text
                    span: full
                content:
                    label: Content
                    type: textarea
                    span: full
```

#### Example

- In the form, each item appears as a collapsible section.
- Clicking "Add new item" adds a new collapsible section with an empty form.
- Saving the form saves each item in the repeater as a separate record in the database table.

This setup provides a flexible and user-friendly interface for managing lists of related data.

---

## Behaviors

### RelationableModel Behavior

The `RelationableModel` behavior allows a `Repeater` to be used as a relations editor via relation behavior.

#### Example

For a `Category` model with multiple `Item` relations:

```php
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
```

In `\models\category\fields.yaml`:

```yaml
fields:
    items_list:
        prompt: Add new item
        span: full
        type: repeater
        cssClass: 'repeater-collapsible repeater-open'
        form: $/models/item/fields.yaml
```

---

## Helper Functions

The plugin provides a collection of helper functions to simplify complex operations.
These can be used across the application to improve code readability and functionality.

| **Function**             | **Description**                                                                                                             |
|--------------------------|-----------------------------------------------------------------------------------------------------------------------------|
| `numencode_partial`      | Returns the path to the NumenCode partial file.                                                                             |
| `validate_request`       | Validates the current request and flashes errors to the session. Returns `true` if the request is valid, `false` otherwise. |
| `select_options`         | Creates options for the `<select>` element.                                                                                 |
| `array_insert`           | Inserts a new element into a specific position within an array.                                                             |
| `array_before`           | Moves a specific array element before another array element.                                                                |
| `array_merge_reference`  | Merges elements from passed arrays into one array, retaining references to the original arrays.                             |
| `array_search_recursive` | Recursively searches an array for a value and returns the corresponding keys if successful.                                  |
| `round_global`           | Rounds a number to a predefined number of decimals set in a global configuration.                                           |
| `plugin_exists`          | Checks if a plugin exists and is enabled.                                                                                   |
| `extend_class`           | Extends a class with a behavior.                                                                                            |
| `dumpbug`                | Dumps a simple debug backtrace.                                                                                             |
| `diebug`                 | Dumps a simple debug backtrace and terminates the script.                                                                   |
| `dd_query`               | Dumps the next database query.                                                                                              |
| `d`                      | Dumps the passed variables without terminating the script.                                                                  |
| `ddd`                    | Resolves rendering issues with `dd()` in the browser's network tab.                                                         |
| `ddt`                    | Dumps a debug backtrace and terminates the script, useful for console debugging.                                            |

---

## CMS Permissions
The `CmsPermissions` class enables fine-grained control over user group actions, such as creating, updating, and deleting data.

### Configuration Example

**Step 1**: Configure permissions in the plugin's `boot()` method:

```php
use NumenCode\Fundamentals\Classes\CmsPermissions;

class Plugin extends PluginBase
{
    public function boot()
    {
        CmsPermissions::revokeDelete('owners', AcmeController::class);
        CmsPermissions::revokeUpdate(['owners', 'publishers'], CustomController::class);
    }
}
```

**Step 2**: Apply permission logic in templates:

```php
<?php if (\NumenCode\Fundamentals\Classes\CmsPermissions::canDelete($controller)): ?>
    <button
        type="button"
        class="oc-icon-trash-o btn-icon danger pull-right"
        data-request="onDelete"
        data-load-indicator="<?= e(trans('backend::lang.form.deleting')) ?>"
        data-request-confirm="<?= e(trans('backend::lang.form.confirm_delete')) ?>">
    </button>
<?php endif; ?>
```

---

## Traits

### Progress Bar

The `ProgressBar` trait displays progress status in the CLI while iterating through an array during console command execution.
The `AutoProgressBar` should be used when you need to display a progress bar in the CMS backend.

#### Parameters

- `int $current`: Current processing element.
- `int $total`: Total number of elements.
- `int $barSize`: The size of the progress bar in blocks.

#### Usage Example

```php
// Progress Bar for CLI
foreach ($haystack as $needle) {
    $this->progressBar(isset($bar) ? ++$bar : $bar=1, count($haystack));
    // All logic comes after this line...
}

// Progress Bar for CMS
foreach ($haystack as $needle) {
    $this->autoProgressBar($haystack, 'progress');
    // All logic comes after this line...
}
```

### Publishable

The `Publishable` trait provides a simple way to manage content visibility using an `is_published` field in the database table.

#### Requirements

Add a boolean field named `is_published` to the table.

#### Features

1. **Automatic Filtering**: Only records with `is_published = true` are included in queries for frontend users.
2. **Override Scope**: Use the `withUnpublished()` method to retrieve all records, including unpublished ones.

#### Usage Example

```php
class Post extends Model
{
    use \NumenCode\Fundamentals\Traits\Publishable;

    protected $fillable = ['title', 'content', 'is_published'];
}

// To fetch only published records:
$posts = Post::all();

// To include unpublished records:
$allPosts = Post::withUnpublished()->get();
```

### Wrapper

The `Wrapper` trait is designed to wrap and extend the functionality of an existing object, providing a flexible way to
interact with the parent object while maintaining access to its properties and methods. This trait acts as a proxy,
delegating calls to the wrapped object, and can be used to enhance or modify its behavior without directly modifying
the parent class.

#### Usage Example

1. Include the `Wrapper` trait in your class.
2. Pass the parent object to the constructor of the class using the `Wrapper` trait.
3. Use the `init()` method in your class to define any custom initialization logic.

```php
class MyWrapper
{
    use \NumenCode\Fundamentals\Traits\Wrapper;

    protected function init($parent)
    {
        // Custom initialization logic
    }
}

// Usage
$parentObject = new ParentClass();
$wrapper = new MyWrapper($parentObject);

$wrapper->someProperty = 'value'; // Delegates to $parentObject
$result = $wrapper->someMethod(); // Calls method on $parentObject

// Accessing the Parent Object: use the getWrappedObject() method to access the original parent object if needed
$original = $wrapper->getWrappedObject();
```

---

## ImageResize Utility

The `ImageResize` utility is a helper class designed to resize images dynamically. It provides an
easy-to-use interface for adjusting image dimensions while maintaining high performance and quality.

### Features
- Resize images to specific dimensions.
- Option to maintain aspect ratio.
- Crop images to fit exact dimensions.
- Specify custom image quality.
- Handles various image formats (e.g., JPEG, PNG, WebP).
- Supports caching of resized images for performance optimization.

### Usage

You can use the `ImageResize` utility in your Twig templates by using `resize` filter to process images.

#### Example: Image resizing in Twig

```html
<img src="{{ post.picture|media|resize('750x300.crop') }}">
```

---

## ImageResizer Utility

The `ImageResizer` utility is a helper class designed to resize all images in the provided content dynamically.

### Usage

You can use the `ImageResizer` utility in your Twig templates by using `resize_images` filter to process images.

#### Example: Resize images in content in Twig

```html
<div class="content">{{ content|resize_images }}"</div>
```

---

## Twig Extensions

Twig extensions provide enhanced template functionality and are divided into two scopes:

### Filters

Use filters to transform data.

| **Filter**      | **Description**                                          | **Usage**                                            |
|-----------------|----------------------------------------------------------|------------------------------------------------------|
| `resize`        | Resize an image and create a thumbnail cache file        | 'picture.jpg'&#124;media&#124;resize('600x400.crop') |
| `resize_images` | Resize all images in HTML content by creating thumbnails | {{ content&#124;resize_images }}                     |
| `str_pad`       | Pad a string to a specific length                        | 'file.pdf'&#124;str_pad(4, '0')                      |
| `url_path`      | Parse URL and return its components                      | 'file.pdf'&#124;url_path                             |

### Functions

Use functions for broader, dynamic operations.

| **Function**     | **Description**                                        | **Usage**                            |
|------------------|--------------------------------------------------------|--------------------------------------|
| `app`            | Retrieves the container instance                       | `app()`                              |
| `asset_hash`     | Generates a cache-busting asset version                | `asset_hash()`                       |
| `class_basename` | Retrieves the "basename" of a class                    | `class_basename('\Namespace\Class')` |
| `collect`        | Creates a collection from a given value                | `collect($array)`                    |
| `config`         | Retrieves or sets configuration values                 | `config('app.key')`                  |
| `d`              | Dumps the passed variables and does not end the script | `d($variable)`                       |
| `dd`             | Dumps the passed variables and ends the script         | `dd($variable)`                      |
| `detect`         | Detects mobile devices                                 | `detect()`                           |
| `device_type`    | Returns 'mobile' or 'desktop' based on detected device | `device_type()`                      |
| `require`        | Reads a file into a string                             | `require('file.txt')`                |
| `trans`          | Translates a string                                    | `trans('backend::lang.form.save')`   |
| `trim`           | Strip whitespaces (or other characters)                | `trim(' Some random sentence ')`     |
| `url_params`     | Retrieves the current routing parameters               | `dd(url_params())`                   |

#### When to Use

- **Filters**: Use when transforming or modifying data inline within Twig.
- **Functions**: Use for accessing dynamic or reusable logic outside of a single data transformation.

---

## Changelog

All notable changes are documented in the [CHANGELOG](CHANGELOG.md).

---

## Contributing

Please refer to the [CONTRIBUTING](CONTRIBUTING.md) guide for details on contributing to this project.

---

## Security

If you identify any security issues, email info@numencode.com rather than using the issue tracker.

---

## Author

The **NumenCode.Fundamentals** plugin is created and maintained by [Blaz Orazem](https://orazem.si/).

For inquiries, contact: info@numencode.com

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

[![License](https://img.shields.io/github/license/numencode/wn-fundamentals-plugin?style=flat-square&color=0099FF)](https://github.com/numencode/wn-fundamentals-plugin/blob/main/LICENSE.md)
