<div
    class="field-relationrepeater"
    data-control="relationrepeater"
    <?php if ($itemActions): ?>data-item-actions="<?= e(json_encode($itemActions, JSON_UNESCAPED_UNICODE)) ?>"<?php endif ?>
>
    <div class="relationrepeater-toggle-all">
        <?php if ($itemActions): ?>
            <button type="button" class="btn btn-default btn-xs wn-icon-clipboard relationrepeater-paste" data-relationrepeater-paste style="display: none">
                <?= e($itemActions['labels']['paste'] ?? '') ?>
            </button>
            <button type="button" class="btn btn-default btn-xs relationrepeater-paste-clear" data-relationrepeater-paste-clear style="display: none" title="<?= e($itemActions['labels']['paste_clear'] ?? '') ?>">&times;</button>
        <?php endif ?>
        <?php if ($toggleAll): ?>
            <button type="button" class="btn btn-default btn-xs wn-icon-angle-double-down" data-relationrepeater-toggle="expand">
                <?= e(trans('numencode.fundamentals::lang.repeater.expand_all')) ?>
            </button>
            <button type="button" class="btn btn-default btn-xs wn-icon-angle-double-up" data-relationrepeater-toggle="collapse">
                <?= e(trans('numencode.fundamentals::lang.repeater.collapse_all')) ?>
            </button>
        <?php endif ?>
    </div>

    <?= $repeater ?>
</div>
