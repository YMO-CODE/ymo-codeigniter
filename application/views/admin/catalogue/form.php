<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$is_part = $type === 'part';
$action = $item ? admin_url('catalogue/'.(int) $item['id'].'/edit') : admin_url('catalogue/new');
$v = function ($k, $default = '') use ($item) {
    $cur = isset($item[$k]) && $item[$k] !== NULL ? $item[$k] : $default;
    if (is_numeric($cur) && in_array($k, array('rate', 'purchase_rate', 'tax_pct', 'low_stock'), TRUE)) {
        $cur = rtrim(rtrim(number_format((float) $cur, 2, '.', ''), '0'), '.');
    }
    return set_value($k, (string) $cur);
};
$incl = set_value('tax_inclusive', isset($item['tax_inclusive']) ? (string) (int) $item['tax_inclusive'] : '1');
?>
<a href="<?= html_escape($back); ?>" class="small">
    <span class="mi mi-sm mi-leading">arrow_back</span><?= $is_part ? 'Spare parts' : 'Services'; ?>
</a>

<div class="ymo-card mt-3" style="max-width: 56rem;">
    <?= validation_errors('<div class="alert alert-danger">', '</div>'); ?>

    <?= form_open($action); ?>
        <input type="hidden" name="type" value="<?= $type; ?>">
        <input type="hidden" name="back" value="<?= html_escape($back); ?>">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="form-floating">
                    <input class="form-control" id="ci_name" name="name" placeholder=" " maxlength="255" required value="<?= html_escape($v('name')); ?>">
                    <label for="ci_name"><?= $is_part ? 'Part name' : 'Service name'; ?></label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating">
                    <input class="form-control" id="ci_cat" name="category" placeholder=" " maxlength="80" value="<?= html_escape($v('category')); ?>">
                    <label for="ci_cat">Category (optional)</label>
                </div>
            </div>
            <div class="col-12">
                <div class="form-floating">
                    <textarea class="form-control" id="ci_desc" name="description" placeholder=" " style="height:96px"><?= html_escape($v('description')); ?></textarea>
                    <label for="ci_desc">Description / what's included (optional)</label>
                </div>
            </div>
            <?php if ($is_part): ?>
            <div class="col-md-5">
                <div class="form-floating">
                    <input class="form-control" id="ci_code" name="code" placeholder=" " maxlength="64" value="<?= html_escape($v('code')); ?>">
                    <label for="ci_code">Barcode / part number</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating">
                    <input class="form-control" id="ci_unit" name="unit" placeholder=" " maxlength="16" value="<?= html_escape($v('unit', 'UNT')); ?>">
                    <label for="ci_unit">Unit (UNT, PCS, LTR)</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating">
                    <input class="form-control" id="ci_purchase" name="purchase_rate" type="number" step="0.01" min="0" placeholder=" " value="<?= html_escape($v('purchase_rate')); ?>">
                    <label for="ci_purchase">Purchase rate (&#8377;)</label>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-md-4">
                <div class="form-floating">
                    <input class="form-control" id="ci_rate" name="rate" type="number" step="0.01" min="0" placeholder=" " value="<?= html_escape($v('rate')); ?>">
                    <label for="ci_rate"><?= $is_part ? 'Selling rate' : 'Rate'; ?> (&#8377;) — blank = set on job</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating">
                    <input class="form-control" id="ci_tax" name="tax_pct" type="number" step="0.01" min="0" max="28" placeholder=" " value="<?= html_escape($v('tax_pct')); ?>">
                    <label for="ci_tax">GST %</label>
                </div>
            </div>
            <div class="col-md-5 d-flex align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="tax_inclusive" id="ci_incl1" value="1" <?= $incl !== '0' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="ci_incl1">Rate includes GST</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="tax_inclusive" id="ci_incl0" value="0" <?= $incl === '0' ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="ci_incl0">GST added on top</label>
                </div>
            </div>
            <?php if ($is_part): ?>
            <div class="col-md-4">
                <div class="form-floating">
                    <input class="form-control" id="ci_low" name="low_stock" type="number" step="0.01" min="0" placeholder=" " value="<?= html_escape($v('low_stock', '0')); ?>">
                    <label for="ci_low">Low stock alert at</label>
                </div>
            </div>
            <div class="col-md-8 small ymo-muted d-flex align-items-center">
                Stock: <?= isset($item['stock']) ? rtrim(rtrim(number_format((float) $item['stock'], 2), '0'), '.') : '0'; ?>. It will move with purchases and job cards once those are built.
                <?php if (isset($item['okm_stock']) && $item['okm_stock'] !== NULL): ?>&nbsp;(OkMechanic showed <?= rtrim(rtrim(number_format((float) $item['okm_stock'], 2), '0'), '.'); ?>.)<?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="ci_active" name="is_active" value="1" <?= (!$item || !empty($item['is_active'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="ci_active">Show when picking items for estimates and jobs</label>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-primary" type="submit"><span class="mi mi-leading">save</span>Save</button>
            <a href="<?= html_escape($back); ?>" class="btn btn-link">Cancel</a>
        </div>
    <?= form_close(); ?>
</div>
