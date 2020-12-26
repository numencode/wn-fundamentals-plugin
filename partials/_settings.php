<div class="column-container">
    <?php foreach ($items as $name => $menu) : ?>
        <div class="column-item">
            <div class="report-widget">
                <h3 style="margin-bottom: 10px;"><?= trans($name); ?></h3>
                <div class="control-status-list">
                    <ul class="nav nav-pills nav-stacked">
                        <?php foreach ($menu as $item) : ?>
                            <li role="presentation" style="padding: 0;">
                                <a style="padding: 10px 15px;" href="<?= $item->url; ?>">
                                    <h5>
                                        <i class="<?= $item->icon ?>"></i>
                                        <?= trans($item->label); ?><br>
                                        <small>
                                            <?= trans($item->description); ?>
                                        </small>
                                    </h5>
                                </a>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>
