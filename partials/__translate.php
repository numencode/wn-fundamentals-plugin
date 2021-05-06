<?php if (plugin_exists('Winter.Translate') && isset($controller->widget->form)) : ?>

    <?php $model = !empty($model) ? $model : $controller->widget->form->model; ?>
    <?php $isTranslatable = $model->isClassExtendedWith(\Winter\Translate\Behaviors\TranslatableModel::class); ?>
    <?php $hasTranslationMask = $isTranslatable && in_array(\Sp\Essentials\Traits\TranslatableMask::class, class_uses($model)); ?>
    <?php $locales = \Winter\Translate\Models\Locale::listAvailable(); ?>

    <div class="translatable-selector">
        <?php if ($isTranslatable) : ?>
            <label class="translatable-label">
                Language:
            </label>
            <div class="form-group-sm">
                <select id="js-lang-select" class="form-control custom-select">
                    <?php foreach ($locales as $code => $locale) : ?>
                        <option value="<?= $code ?>"><?= strtoupper($code) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group-sm">
                <select id="js-lang-copy" class="form-control custom-select">
                    <option value="">- Copy from -</option>
                    <?php foreach ($locales as $code => $locale) : ?>
                        <option value="<?= $code ?>"><?= strtoupper($code) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <?php if ($hasTranslationMask) : ?>
            <div class="translatable-mask">
                <label class="translatable-label">
                    Show in:
                </label>
                <div class="check-group inline-items">
                    <?php foreach ($locales as $code => $locale) : ?>
                        <div class="checkbox custom-checkbox">
                            <input type="checkbox"
                                   name="__trans_mask[]"
                                   value="<?= $code ?>"
                                   id="trans-<?= $code ?>-<?= class_basename($model) ?>"
                                   <?= $model->isVisibleInLocale($code) ? 'checked ' : '' ?>
                            />
                            <label for="trans-<?= $code ?>-<?= class_basename($model) ?>">
                                <?= strtoupper($code) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <input type="hidden" name="__trans_mask_enabled" value="1" />
        <?php endif; ?>
    </div>

<?php endif; ?>
