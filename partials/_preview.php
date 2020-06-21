<?php $sidebar = empty($sidebar) && !empty($controller->bodyClass) && $controller->bodyClass == 'compact-container' ? true : !empty($sidebar); ?>

<?php Block::put('breadcrumb') ?>
    <ul>
        <li><a href="<?= $url ?>"><?= trans($controller->asExtension('ListController')->getConfig()->title) ?></a></li>
        <li><?= e($controller->pageTitle) ?></li>
    </ul>
<?php Block::endPut() ?>

<?php if (!$controller->fatalError): ?>

    <?php if(empty($sidebar)): ?>

        <div class="form-preview">
            <?= $controller->formRenderPreview() ?>
        </div>
        <p>
            <a href="<?= $url ?>" class="btn btn-default oc-icon-chevron-left">
                <?= e(trans('backend::lang.form.return_to_list')) ?>
            </a>
        </p>

    <?php else: ?>

        <?php Block::put('form-contents') ?>
            <div class="layout-row min-size">
                <?= $controller->formRender(['section' => 'outside', 'preview' => true]) ?>
            </div>
            <div class="layout-row">
                <?= $controller->formRender(['section' => 'primary', 'preview' => true]) ?>
            </div>
            <p>
                <a href="<?= $url ?>" class="btn btn-default oc-icon-chevron-left">
                    <?= e(trans('backend::lang.form.return_to_list')) ?>
                </a>
            </p>
        <?php Block::endPut() ?>

        <?php Block::put('form-sidebar') ?>
            <div class="hide-tabs"><?= $controller->formRender(['section' => 'secondary', 'preview' => true]); ?></div>
        <?php Block::endPut() ?>

        <?php Block::put('body') ?>
            <?= Form::open(['class'=>'layout']) ?>
                <?= $controller->makeLayout('form-with-sidebar') ?>
                <?php if (!empty($custom)): ?>
                    <?= $custom ?>
                <?php endif; ?>
            <?= Form::close() ?>
        <?php Block::endPut() ?>

    <?php endif; ?>

<?php else: ?>

    <div class="padded-container">
        <p class="flash-message static error"><?= e(trans($controller->fatalError)) ?></p>
        <p><a href="<?= $url ?>" class="btn btn-default"><?= e(trans('backend::lang.form.return_to_list')) ?></a></p>
    </div>

<?php endif ?>
