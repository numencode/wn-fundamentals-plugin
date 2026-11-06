<div class="field-relationrepeater" data-control="relationrepeater">
    <div class="relationrepeater-toggle-all">
        <button type="button" class="btn btn-default btn-xs wn-icon-angle-double-down" data-relationrepeater-toggle="expand">
            <?= e(trans('numencode.fundamentals::lang.repeater.expand_all')) ?>
        </button>
        <button type="button" class="btn btn-default btn-xs wn-icon-angle-double-up" data-relationrepeater-toggle="collapse">
            <?= e(trans('numencode.fundamentals::lang.repeater.collapse_all')) ?>
        </button>
    </div>

    <?= $repeater ?>
</div>
