# Fundamentals Plugin

The **Fundamentals Plugin** is a toolbox for Winter CMS projects: inline editing of related records with a relation
repeater, publishable models, theme-overridable component layouts, small backend improvements, Twig functions and
PHP helpers. It is also the foundation of the other [NumenCode](https://www.numencode.com) plugins.

[![Version](https://img.shields.io/github/v/release/numencode/wn-fundamentals-plugin?style=flat-square&color=0099FF)](https://github.com/numencode/wn-fundamentals-plugin/releases)
[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/numencode/wn-fundamentals-plugin?style=flat-square&color=0099FF)](https://packagist.org/packages/numencode/wn-fundamentals-plugin)
[![Tests](https://img.shields.io/github/actions/workflow/status/numencode/wn-fundamentals-plugin/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/numencode/wn-fundamentals-plugin/actions)
[![License](https://img.shields.io/github/license/numencode/wn-fundamentals-plugin?label=open%20source&style=flat-square&color=0099FF)](https://github.com/numencode/wn-fundamentals-plugin/blob/main/LICENSE.md)

---

## Features

- **Relation repeater** – edit a model's "has many" records inline in a repeater (`relationrepeater` form widget and
  `RelationableModel` behavior), including validation, ordering and Winter.Translate translations.
- **Winter.Translate integration** – translatable fields inside relation repeater items, plus a language selector that
  switches and copies all translations of a form at once.
- **Publishable models** – hide unpublished records on the frontend, while backend users can still preview them.
- **Component layouts** – let editors choose a layout for a component and override layouts in the theme.
- **CMS permissions** – revoke create, update, delete or duplicate actions on a controller for backend user groups.
- **Config overrides** – change other plugins' YAML configuration (fields, columns, ...) from code.
- **Twig** – `|resize_images`, `|url_path`, `asset_hash()`, `device_type()`, `detect()`, `collect()`, debug helpers
  and more.
- **PHP helpers** – array helpers, `plugin_exists()`, `extend_class()` and debugging helpers.
- **Backend improvements** – icon switch columns, dynamic section titles and Winter.Pages settings in the main tabs;
  each can be switched off.
- **Common translations** for field labels used across NumenCode plugins.

## Requirements

- PHP 8.2 or newer
- [Winter CMS](https://wintercms.com) 1.2 (tested with 1.2.15)
- Optional: [Winter.Translate](https://github.com/wintercms/wn-translate-plugin) for the translation features

## Installation

```bash
composer require numencode/wn-fundamentals-plugin
```

The plugin has no database tables, so no migrations are needed.

## Configuration

The plugin makes a few changes to the whole backend. Each one is enabled by default and can be switched off by
creating `config/numencode/fundamentals/config.php` in your project:

```php
<?php

return [
    'backend' => [
        'switch_column_icons'        => true,  // switch list columns as a green check / red cross
        'pages_viewbag_primary_tabs' => true,  // Winter.Pages: page settings in the main tabs
        'assets'                     => true,  // backend JS/CSS: dynamic section titles, Translate language selector
    ],
];
```

| Option | What it does |
|---|---|
| `switch_column_icons` | List columns of type `switch` show a green check or a red cross with the translated Yes/No label. |
| `pages_viewbag_primary_tabs` | Winter.Pages: the page settings (`viewBag` fields) are shown in the primary tabs instead of the sidebar. |
| `assets` | Loads the plugin's backend JS and CSS on every backend page. Required for dynamic section titles and the Translate language selector. |

## Relation repeater

Edit a model's "has many" related records directly in its form, as repeater items. Each item is a real related
record: it is created, updated, reordered and deleted together with the parent model, its own validation rules apply,
and its translatable fields can be translated.

**1. Parent model** – implement the behavior and map a form field to the relation:

```php
class Gallery extends Model
{
    public $implement = ['NumenCode.Fundamentals.Behaviors.RelationableModel'];

    // form field name => relation name
    public $relationable = [
        'items_list' => 'items',
    ];

    public $hasMany = [
        'items' => [GalleryItem::class, 'key' => 'gallery_id', 'delete' => true],
    ];
}
```

**2. Form field** – use the `relationrepeater` widget with the related model's fields:

```yaml
items_list:
    label: Items
    type: relationrepeater
    form: $/acme/demo/models/galleryitem/fields.yaml
    titleFrom: title
    style: collapsed
    showToggleAll: true
```

All options of the core `repeater` widget work (`titleFrom`, `style`, `minItems`, `maxItems`, `sortable`, ...).
`showToggleAll: true` adds "Expand all" and "Collapse all" buttons above the items. They are shown in list mode
with the `default` or `collapsed` style, not with `accordion` (where only one item is open) or in grid mode.
The related model's `fields.yaml` **must** contain its primary key as a hidden field. Without it, existing items are
not recognized, and every save deletes and recreates all related records:

```yaml
fields:
    id:
        type: number
        cssClass: hidden
    title:
        label: Title
```

### How saving works

- Items with the ID of an existing related record update that record; items without one create new records.
  An ID that doesn't belong to the parent is never used to update another record.
- Related records that are no longer in the repeater are deleted.
- When the related model uses the `Sortable` trait, the item order is stored in its sort order column.
- Related models that use the `Validation` trait are validated together with the parent. The error names the item,
  e.g. "Item 2: The title field is required.", and nothing is saved.
- For extra rules across all items, add a `validate{FieldName}` method to the parent model. Return an error message,
  or `null` when the items are valid:

  ```php
  public function validateItemsList(array $items): ?string
  {
      return count($items) > 10 ? 'A gallery can have at most 10 items.' : null;
  }
  ```

- The related records are saved after the parent, in a database transaction.

## Winter.Translate integration

When [Winter.Translate](https://github.com/wintercms/wn-translate-plugin) is installed:

- **Translatable fields inside relation repeater items** become multilingual fields (Translate itself skips nested
  forms). The translations are stored on each related record, so the related model only needs the
  `TranslatableModel` behavior and its `$translatable` attributes.
- **Language selector** – backend forms with translatable fields (on the model or on its relation repeater items)
  get a language selector at the top. It switches all multilingual fields of the form to one language and can copy all
  translations of the form from another language. It requires at least two enabled locales and the `assets` option.

Without Translate, the plugin works the same, without these features.

## Publishable models

Hide unpublished records on the frontend. Logged-in backend users still see them, so they can preview content before
publishing it; the backend and console commands are not affected.

```php
use NumenCode\Fundamentals\Traits\Publishable;

class Post extends Model
{
    use Publishable;
}
```

The model needs a boolean `is_published` column. To use another column, define a constant:

```php
const PUBLISHED_COLUMN = 'active';
```

```php
$post->isPublished();              // bool
Post::withUnpublished()->get();    // all records, also on the frontend
Post::onlyUnpublished()->get();    // only unpublished records
```

## Component layouts

The `ComponentRenderer` trait lets editors choose a layout (partial) for a component in the CMS, and lets themes
override and add layouts.

```php
use NumenCode\Fundamentals\Traits\ComponentRenderer;

class Gallery extends ComponentBase
{
    use ComponentRenderer;

    public function defineProperties()
    {
        return [
            'layout' => [
                'title'   => 'Layout',
                'type'    => 'dropdown',
                'default' => '@default',
            ],
        ];
    }
}
```

Layout selection is enabled per component class, e.g. in your theme plugin's `boot()` method:

```php
Gallery::$overrideLayout = true;
```

- The **Layout** dropdown lists the component's own partials and the partials in the theme folder named after the
  component class: `themes/<theme>/partials/gallery/` for a `Gallery` component – no matter which alias the
  component has on a page. Partials starting with `_` are not listed.
- The selected layout is rendered from that theme folder when it exists there, otherwise from the component.
- Winter's own overrides in `themes/<theme>/partials/<alias>/` keep working.
- Without `$overrideLayout`, the component renders its `default` partial.

In a page or layout:

```ini
[gallery homeGallery]
layout = "@homepage"
```

renders `themes/<theme>/partials/gallery/homepage.htm`.

> The trait implements the component's `onRender()` method, so the component must not define its own.

## CMS permissions

Revoke backend actions on a controller for backend user groups (by group code):

```php
use NumenCode\Fundamentals\Classes\CmsPermissions;

public function boot()
{
    CmsPermissions::revokeDelete(['owners', 'publishers'], \Acme\Demo\Controllers\Posts::class);
    CmsPermissions::revokeDuplicate('publishers', \Acme\Demo\Controllers\Posts::class);
}
```

Check them in the controller or its views:

```php
if (CmsPermissions::canDelete($this)) {
    // ...
}
```

Available: `canCreate`, `canUpdate`, `canDelete`, `canDuplicate` and `revokeCreate`, `revokeUpdate`, `revokeDelete`,
`revokeDuplicate`. A user is denied an action when any of their groups has it revoked.

## Config overrides

Change the YAML configuration of other plugins and modules from code, without copying their files:

```php
use NumenCode\Fundamentals\Bootstrap\ConfigOverride;

ConfigOverride::extendFields(\Winter\Blog\Models\Post::class, function (array $config) {
    $config['fields']['subtitle'] = ['label' => 'Subtitle', 'type' => 'text'];

    return $config;
});
```

| Method | Config file |
|---|---|
| `extendFields($class, $callback)` | `fields.yaml` |
| `extendColumns($class, $callback)` | `columns.yaml` |
| `extendImportColumns($class, $callback)` | `columns_import.yaml` |
| `extendExportColumns($class, $callback)` | `columns_export.yaml` |
| `extendImportExport($class, $callback)` | `config_import_export.yaml` |
| `extend($file, $class, $callback)` | any file in the class's config folder |
| `extendAll($callback)` | every config file; the callback receives `($publicPath, $config)` |

The callback receives the configuration as an array and returns the changed array (or `null` to leave it unchanged).

## Twig filters and functions

| Name | Type | Description |
|---|---|---|
| `resize_images` | filter | Replaces media library images with a pixel width (`style="width: 300px"` or `width="300"`) in HTML content with a resized copy made by Winter's image resizer: `{{ content\|resize_images\|raw }}` |
| `url_path` | filter | Path of a URL: `{{ 'https://example.com/a/b?c=1'\|url_path }}` → `/a/b` |
| `asset_hash(path)` | function | Cache-busting token. With a path (relative to the theme or the project, or a URL), a short hash that changes when the file changes: `{{ 'assets/css/app.css'\|theme }}?v={{ asset_hash('assets/css/app.css') }}`. Without a path, a token that changes when the cache is cleared. |
| `device_type()` | function | `mobile`, `tablet` or `desktop`, based on the user agent |
| `detect()` | function | The request's [MobileDetect](https://github.com/serbanghita/Mobile-Detect) instance: `{% if detect().isiOS() %}` |
| `collect(items)` | function | A Laravel collection: `{{ collect(items).sortBy('title').pluck('title').implode(', ') }}` |
| `class_basename(class)` | function | Class name without the namespace |
| `d(...)`, `dd(...)` | function | Dump (and die) – only when `app.debug` is enabled, otherwise they do nothing |

> `device_type()` and `detect()` read the user agent. With full-page caching or a CDN, the cached HTML is served to
> every device – prefer responsive CSS where possible.

For images in your own templates, use Winter's built-in `|resize` filter: `{{ image|media|resize(700, 400, {mode: 'crop'}) }}`.

## PHP helpers

| Helper | Description |
|---|---|
| `plugin_exists('Winter.Translate')` | Whether a plugin is installed and enabled |
| `extend_class(Post::class, Behavior::class)` | Extends an extendable class (model, controller, ...) with a behavior |
| `array_insert($array, $before, $data)` | Inserts `$data` before a key (string) or position (int); appends when the key doesn't exist |
| `array_before($array, $needle, $move)` | Moves the element with key `$move` directly before the element with key `$needle` |
| `array_search_recursive($needle, $array)` | Path of keys to a value in a multidimensional array, e.g. `['fields', 'title', 'label']` |
| `dd_query($count = 1)` | Dumps the executed SQL queries with their bindings and dies after the `$count`-th one |
| `ddd(...)` | `dd()` with HTTP status 500, easy to spot in the browser's network tab (AJAX debugging) |
| `ddt($skip = 0, $die = true)` | Prints a simple backtrace (file : line – function) |

```php
$fields = array_insert($fields, 'content', ['subtitle' => ['label' => 'Subtitle']]);
```

## Backend improvements

- **Switch columns** – list columns of type `switch` show a green check or a red cross with the translated Yes/No
  label (option `switch_column_icons`).
- **Winter.Pages** – page settings (title, URL, meta data) are shown in the primary tabs instead of the sidebar
  (option `pages_viewbag_primary_tabs`).
- **Dynamic section titles** – a section field can show values of other fields in the same form or repeater item,
  updated while typing. `[original]` is the section's own label:

  ```yaml
  section:
      label: Item
      type: section
      containerAttributes:
          data-set-from: '[original]: [title]'
  ```

## Common translations

Labels used across NumenCode plugins, for reuse in your own `fields.yaml` and `columns.yaml`:

```yaml
title:
    label: numencode.fundamentals::lang.field.title
is_published:
    label: numencode.fundamentals::lang.field.is_published
    comment: numencode.fundamentals::lang.field.is_published_comment
```

Groups: `field.*` (id, title, subtitle, description, content, picture, link, slug, sort order, ...), `timestamp.*`
(created, updated, deleted, ...) and `item.*` (item labels and actions). See [`lang/en/lang.php`](lang/en/lang.php).

The plugin is translated into English and Slovenian (`lang/sl`).

## Upgrading from 1.x

Version 2.0 removes several features and changes some APIs. See the upgrade guide in the
[CHANGELOG](CHANGELOG.md#upgrading-from-1x).

## Changelog

All notable changes are documented in the [CHANGELOG](CHANGELOG.md).

## Contributing

Please refer to the [CONTRIBUTING](CONTRIBUTING.md) guide for details on contributing to this project.

## Security

If you discover a security issue, please email <info@numencode.com> instead of using the issue tracker.

## Author

The **NumenCode.Fundamentals** plugin is created and maintained by [Blaz Orazem](https://orazem.si/).
More NumenCode plugins: [numencode.com](https://www.numencode.com).

For inquiries, contact: <info@numencode.com>

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
