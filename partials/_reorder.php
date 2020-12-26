<?php $sidebar = empty($sidebar) && !empty($controller->bodyClass) && $controller->bodyClass == 'compact-container' ? true : !empty($sidebar); ?>

<?php Block::put('breadcrumb') ?>
    <ul>
        <li><a href="<?= $url ?>"><?= trans($controller->asExtension('ListController')->getConfig()->title) ?></a></li>
        <li><?= e($controller->pageTitle) ?></li>
    </ul>
<?php Block::endPut() ?>

<?php Block::put('body') ?>
    <?php if (empty($sidebar)) : ?>
        <?= $controller->reorderRender() ?>
    <?php else : ?>
        <div class="padded-container" style="padding-top: 0;">
            <?= $controller->reorderRender() ?>
        </div>
    <?php endif ?>
<?php Block::endPut() ?>
