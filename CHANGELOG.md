# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2026-11-06

A rewrite focused on security, Winter CMS 1.2.15 and a clean, documented toolbox. It contains breaking changes –
see [Upgrading from 1.x](#upgrading-from-1x).

### Security

- Removed the Twig functions `app()`, `config()` and `require()`. They let anyone who can edit CMS templates read
  files (e.g. `.env`) and configuration values (e.g. database passwords) and access any service of the application,
  bypassing Winter's Twig security policy.
- Removed the plugin's image thumbnail routes. They resized images to any size requested in the URL (disk and CPU
  exhaustion), did not block `..` in paths and created folders with `0777` permissions.
- Fixed unescaped output (XSS) in the removed `select_options()` helper, the replaced Settings page and the Translate
  language selector.
- The Twig `d()` and `dd()` functions only output anything when `app.debug` is enabled.

### Added

- **`relationrepeater` form widget** for models with the `RelationableModel` behavior. It extends the core repeater
  (all its options work) and binds every item form to its own related record.
- **Configuration switches** for the backend changes (`config/numencode/fundamentals/config.php`):
  `switch_column_icons`, `pages_viewbag_primary_tabs` and `assets`.
- **Translate language selector** for every backend form with translatable fields (on the model or on its relation
  repeater items). It switches all fields to one language and copies all translations of the form from another
  language, using Winter.Translate's own field controls.
- `Publishable`: `onlyUnpublished()`, a configurable column (`const PUBLISHED_COLUMN`), `getPublishedColumn()`.
- `CmsPermissions`: `canDuplicate()` and `revokeDuplicate()`.
- `asset_hash('path')`: a hash of the file's modification time, so it changes whenever the file changes.
- `device_type()` returns `tablet` for tablets (before: `mobile`).
- `ComponentRenderer`: the layout dropdown also lists the component's own partials.
- Winter.Translate is listed as a suggested package.

### Changed

- **`RelationableModel` rewritten**:
  - Items are matched to related records by ID (before: by position).
  - Related models are validated as the records that are actually saved. Validation errors name the item
    ("Item 2: ...").
  - Related records are saved in a database transaction, with a single save per record.
  - IDs that don't belong to the parent create new records instead of being used.
  - Unpublished related records are kept when saving outside the backend.
  - The optional `validate{FieldName}(array $items)` method is now defined on the parent model.
- **`ComponentRenderer`** no longer changes the component's alias and no longer defines `init()`. Theme layouts in
  `partials/<class name>/` are found directly, so AJAX handlers and `{{ __SELF__ }}` use the page alias, and components
  can have their own `init()`.
- **`|resize_images`** uses Winter's core image resizer, only processes media library images with a pixel width and
  keeps all other attributes of the image tag.
- Switch list columns use the translated Yes/No labels and CSS classes.
- Translatable fields in relation repeater items are handled through Winter.Translate's public API, only for relation
  repeater items (before, Translate's event registry was replaced globally and also affected core repeaters).
- `plugin_exists()` uses Winter's `PluginManager::exists()` (case-insensitive, respects disabled plugins).
- `array_insert()` appends the data when the key doesn't exist (before: inserted it at the beginning).
- `dd_query()` substitutes bindings in one pass (no endless loop when a value contains `?`) and quotes them properly.
- `url_path` returns an empty string for empty input.
- Strict types, parameter and return types and PSR-12 throughout.
- Requires **PHP 8.2+**. Tested with Winter CMS 1.2.15; supports `composer/installers` v1 and v2.
- CI: tests on PHP 8.2–8.5 against Winter 1.2 (plus an informational job against Winter `develop`), PHP syntax,
  `composer validate` and Markdown linting.

### Fixed

- Exceptions thrown inside the plugin's backend partials were silently swallowed.
- Relation repeater items were editable on preview pages.
- Dynamic section titles (`data-set-from`) failed for patterns without field placeholders.
- The `Publishable` scope condition used a bitwise operator.

### Removed

- **Image resizer**: the plugin's `|resize('700x400.crop')` filter (it replaced Winter's core `|resize` filter),
  the thumbnail routes, `ImageResize`, `ImageResizeManager`, the missing-image placeholder, the `cors` mode and the
  png/jpg conversion fallback.
- **Repeater override**: the plugin no longer replaces the core `repeater` widget. Use `relationrepeater`.
- **Collapsible repeaters** (`repeater-collapsible`, `repeater-open` CSS classes) and the CSS that hid the core
  repeater's collapse buttons – use the core options `titleFrom` and `style` instead.
- **Backend controller views** (`_create`, `_update`, `_preview`, `_index`, `_list_toolbar`, `_reorder`,
  `__create_buttons`, `__update_buttons`) – Winter's built-in default views do the same.
- **Settings page replacement** (column layout of System › Settings).
- Clearing the asset combiner cache when the backend brand settings are saved (Winter handles the brand CSS cache).
- **Twig**: `app()`, `config()`, `require()`, `trans()`, `trim()`, `url_params()` and the `str_pad` filter.
- **Helpers**: `numencode_partial()`, `validate_request()`, `select_options()`, `array_merge_reference()`,
  `round_global()`, `d()`, `dumpbug()` and `diebug()`.
- **Traits**: `ProgressBar` and `Wrapper`.
- The "Show in" translation mask and the registration of the non-existing `TranslatableHelper` form widget.
- Support for PHP 8.0 and 8.1 (end of life).

### Upgrading from 1.x

1. **PHP 8.2** or newer is required.
2. **Relation repeaters** – for every model with the `RelationableModel` behavior:
   - change `type: repeater` to `type: relationrepeater` in its `fields.yaml`;
   - make sure the related model's `fields.yaml` contains its primary key as a hidden field (`id` with
     `cssClass: hidden`), otherwise every save recreates the related records;
   - replace `cssClass: 'repeater-collapsible repeater-open'` with the core options, e.g. `titleFrom: title` and
     `style: collapsed` (or `accordion`), and remove the item `section` field with `data-set-from: '[title]'` if it
     was only used as the item title;
   - move custom `validate{FieldName}()` methods from a `RelationableModel` subclass to the parent model.
3. **Controller views** – delete controller `.htm` files that only call `numencode_partial('_create.php', ...)`,
   `_update.php`, `_preview.php`, `_index.php` or `_list_toolbar.php`; Winter then renders its default views. Set
   `formLayout: sidebar` in `config_form.yaml` if the form used the sidebar layout. The Translate language selector is
   added automatically.
4. **Images** – replace `|resize('700x400.crop')` with Winter's filter: `|resize(700, 400, {mode: 'crop'})`.
   Old `/thumb/` image URLs no longer work; the generated `thumb` folders in `storage/app/media` can be deleted.
5. **Twig** – replace removed functions:

   | 1.x | 2.0 |
   |---|---|
   | `trans('key')` | `'key'\|trans` |
   | `url_params()` | `this.param` |
   | `trim(value)` | `value\|trim` |
   | `value\|str_pad(3, '0')` | `str_padLeft(value, 3, '0')` |
   | `config()`, `app()`, `require()` | pass the values from a component or page code instead |

6. **PHP helpers** – replace removed helpers:

   | 1.x | 2.0 |
   |---|---|
   | `d($var)`, `dumpbug($var)` | `dump($var)` |
   | `diebug($var)` | `dd($var)` |
   | `array_merge_reference(...)` | `array_replace(...)` |
   | `select_options($options)` | `Form::select()` |
   | `validate_request($rules)` | `$request->validate($rules)` or `Validator::make()` |
   | `round_global($value)` | `round($value, $decimals)` |
   | `ProgressBar` trait | `$this->withProgressBar($items, fn ($item) => ...)` in Artisan commands |

7. **Components with `ComponentRenderer`** – `$overrideLayout` and the `layout` property work as before; theme layouts
   stay in `themes/<theme>/partials/<class name>/`. Remove workarounds for the trait's `init()` method, and make sure
   the component doesn't define its own `onRender()`.
8. **Backend changes** can now be switched off – see [Configuration](README.md#configuration).

## [1.1.0] - 2025-03-20

### Added

- `ComponentRenderer` trait.
- Test suite for the `ComponentRenderer` trait.
- README additions.

## [1.0.1] - 2025-03-06

### Changed

- GitHub workflows.
- Minor README improvements.

## [1.0.0] - 2025-01-12

### Added

- Initial release of the **Fundamentals Plugin**.
- Core functionality for Winter CMS projects, compatible with Winter CMS `1.2.7`.
- Prerequisite for the other NumenCode plugins.

[Unreleased]: https://github.com/numencode/wn-fundamentals-plugin/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/numencode/wn-fundamentals-plugin/compare/v1.1.0...v2.0.0
[1.1.0]: https://github.com/numencode/wn-fundamentals-plugin/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/numencode/wn-fundamentals-plugin/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/numencode/wn-fundamentals-plugin/releases/tag/v1.0.0
