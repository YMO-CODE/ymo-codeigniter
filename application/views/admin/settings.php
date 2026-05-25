<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="ymo-card">
    <p class="ymo-muted small mb-4">
        <span class="mi mi-sm mi-leading">info</span>
        Gateway credentials and other secrets live in environment variables (see <code>.env.example</code>). The toggles below are operational knobs you can change at runtime.
    </p>

    <?= form_open(admin_url('settings')); ?>
        <?php foreach ($editable as $key => $meta): $val = isset($values[$key]) ? $values[$key] : ''; ?>
            <?php if ($meta['type'] === 'bool'): ?>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="<?= $key; ?>" value="0">
                        <input class="form-check-input" type="checkbox" id="set_<?= $key; ?>" name="<?= $key; ?>" value="1"
                            <?= $val === '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="set_<?= $key; ?>"><?= html_escape($meta['label']); ?></label>
                    </div>
                </div>
            <?php elseif ($meta['type'] === 'int'): ?>
                <div class="form-floating mb-3">
                    <input class="form-control" type="number" id="set_<?= $key; ?>" name="<?= $key; ?>" placeholder=" "
                        min="<?= (int) ($meta['min'] ?? 0); ?>" max="<?= (int) ($meta['max'] ?? 99999); ?>"
                        value="<?= html_escape($val); ?>">
                    <label for="set_<?= $key; ?>"><?= html_escape($meta['label']); ?></label>
                </div>
            <?php elseif ($meta['type'] === 'email'): ?>
                <div class="form-floating mb-3">
                    <input class="form-control" type="email" id="set_<?= $key; ?>" name="<?= $key; ?>" placeholder=" "
                        value="<?= html_escape($val); ?>">
                    <label for="set_<?= $key; ?>"><?= html_escape($meta['label']); ?></label>
                </div>
            <?php else: ?>
                <div class="form-floating mb-3">
                    <input class="form-control" id="set_<?= $key; ?>" name="<?= $key; ?>" placeholder=" "
                        value="<?= html_escape($val); ?>">
                    <label for="set_<?= $key; ?>"><?= html_escape($meta['label']); ?></label>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <button class="btn btn-primary" type="submit">
            <span class="mi mi-leading">save</span>Save
        </button>
    <?= form_close(); ?>
</div>
