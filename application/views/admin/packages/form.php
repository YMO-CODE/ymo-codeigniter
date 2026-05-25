<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$action = $package ? admin_url('packages/'.$package['id'].'/edit') : admin_url('packages/new');
$features = !empty($package['features']) ? $package['features'] : array('');
if ($_POST && !empty($_POST['features'])) {
    $features = $_POST['features'];
}
?>
<a href="<?= admin_url('packages'); ?>" class="small">
    <span class="mi mi-sm mi-leading">arrow_back</span>All packages
</a>

<div class="ymo-card mt-3">
    <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

    <?= form_open($action); ?>
        <div class="row g-3">
            <div class="col-md-8">
                <div class="form-floating">
                    <input class="form-control" id="pk_name" name="name" placeholder=" "
                           value="<?= set_value('name', $package['name'] ?? ''); ?>" required>
                    <label for="pk_name">Name</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <input class="form-control" id="pk_price" name="price" type="number" step="0.01" min="0" placeholder=" "
                           value="<?= set_value('price', isset($package['price']) ? $package['price'] : ''); ?>" required>
                    <label for="pk_price">Price (&#8377;)</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <input class="form-control" id="pk_sort" name="sort_order" type="number" placeholder=" "
                           value="<?= set_value('sort_order', isset($package['sort_order']) ? $package['sort_order'] : 100); ?>">
                    <label for="pk_sort">Sort</label>
                </div>
            </div>
            <div class="col-12">
                <div class="form-floating">
                    <textarea class="form-control" id="pk_summary" name="summary" placeholder=" "
                              rows="2" maxlength="500" style="height:90px;"><?= set_value('summary', $package['summary'] ?? ''); ?></textarea>
                    <label for="pk_summary">Summary (shown under the package title)</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label small ymo-muted"><span class="mi mi-sm mi-leading">format_list_bulleted</span>Features</label>
                <div id="featureList">
                    <?php foreach ($features as $f): ?>
                        <input class="form-control mb-2" name="features[]" value="<?= html_escape($f); ?>" placeholder="e.g. Engine oil change">
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="(function(){var i=document.createElement('input');i.className='form-control mb-2';i.name='features[]';i.placeholder='Feature line';document.getElementById('featureList').appendChild(i);i.focus();})()">
                    <span class="mi mi-sm mi-leading">add</span>Add line
                </button>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                        <?= !empty($package['is_active']) || !$package ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">Visible to customers</label>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-primary" type="submit">
                <span class="mi mi-leading">save</span>Save
            </button>
            <a href="<?= admin_url('packages'); ?>" class="btn btn-link">Cancel</a>
        </div>
    <?= form_close(); ?>
</div>
