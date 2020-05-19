<div class="form-buttons">
    <div class="loading-indicator-container">
        <button
            type="submit"
            data-request="onSave"
            data-request-data="redirect:0"
            data-hotkey="ctrl+s, cmd+s"
            data-load-indicator="<?= e(trans('backend::lang.form.saving')) ?>"
            data-request-success="if($('.refresh-on-upload .upload-object').length) location.reload()"
            class="btn btn-primary">
            <?= e(trans('backend::lang.form.save')) ?>
        </button>
        <button
            type="button"
            data-request="onSave"
            data-request-data="close:1"
            data-hotkey="ctrl+enter, cmd+enter"
            data-load-indicator="<?= e(trans('backend::lang.form.saving')) ?>"
            class="btn btn-default">
            <?= e(trans('backend::lang.form.save_and_close')) ?>
        </button>
        <?php if(\NumenCode\Fundamentals\Classes\CmsPermissions::canDelete($controller)): ?>
            <button
                type="button"
                class="oc-icon-trash-o btn-icon danger pull-right"
                data-request="onDelete"
                data-load-indicator="<?= e(trans('backend::lang.form.deleting')) ?>"
                data-request-confirm="<?= e(trans('backend::lang.form.confirm_delete')) ?>">
            </button>
        <?php endif; ?>
        <span class="btn-text">
            <?= e(trans('backend::lang.form.or')) ?>
            <a href="<?= $url . (!empty($urlParam) ? '/' . $urlParam : '') ?>">
                <?= e(trans('backend::lang.form.cancel')) ?>
            </a>
        </span>
    </div>
</div>
