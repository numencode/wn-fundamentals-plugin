# Fundamentals plugin

This plugin contains some fundamental functionalities that facilitate application development.

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
Model entity must use the Publishable trait and the table must include field is_published.

**Usage in Model**

    class Acme extends Model
    {
        use \NumenCode\Fundamentals\Traits\Publishable;
        
        ...
    }

## Relationable

Relationable trait enables `repeater` to be used as relations via Relation behavior.

**Usage in Model**

    class Acme extends Model
    {
        use \NumenCode\Fundamentals\Traits\Relationable;
        
        ...
    }
