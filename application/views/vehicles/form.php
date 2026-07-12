<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="ymo-card">
                <h1 class="h3 mb-4"><span class="mi mi-leading">directions_car</span><?= html_escape($title); ?></h1>
                <?php if (!$vehicle && !empty($next)): ?>
                    <p class="ymo-muted small mb-3">Add your car details to continue booking.</p>
                <?php endif; ?>

                <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

                <?php
                $action = $vehicle ? site_url('vehicles/'.$vehicle['id'].'/edit') : site_url('vehicles/new');
                if (!$vehicle && !empty($next)) {
                    $action .= '?next='.rawurlencode($next);
                }
                echo form_open_multipart($action);
                ?>
                    <?php if (!$vehicle && !empty($next)): ?>
                        <input type="hidden" name="next" value="<?= html_escape($next); ?>">
                    <?php endif; ?>
                    <div class="form-floating mb-3">
                        <select name="make_id" id="vh_make" class="form-select" required>
                            <option value="">— Select brand —</option>
                            <?php foreach ($makes as $m): ?>
                                <option value="<?= (int) $m['id']; ?>"
                                    <?= set_select('make_id', $m['id'], !empty($vehicle) && (int) $vehicle['make_id'] === (int) $m['id']); ?>>
                                    <?= html_escape($m['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="vh_make">Vehicle company</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input class="form-control" id="vh_variant" name="variant" placeholder=" "
                               value="<?= set_value('variant', !empty($vehicle) ? $vehicle['variant'] : ''); ?>" required>
                        <label for="vh_variant">Variant / model (e.g. Swift VXi 2021)</label>
                    </div>
                    <div class="form-floating mb-1">
                        <input class="form-control font-monospace" id="vh_number" name="vehicle_number" placeholder=" "
                               value="<?= set_value('vehicle_number', !empty($vehicle) ? $vehicle['vehicle_number'] : ''); ?>"
                               data-uppercase required>
                        <label for="vh_number">Vehicle number (e.g. MH12AB1234)</label>
                    </div>
                    <div class="md-field-help mb-3">Indian format. Spaces and dashes are okay — we'll clean them up.</div>

                    <div class="mb-3">
                        <label class="form-label small ymo-muted"><span class="mi mi-sm mi-leading">photo_camera</span>Photo (optional, max 3 MB)</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                        <?php if (!empty($vehicle['image_path'])): ?>
                            <div class="mt-2"><img src="<?= base_url($vehicle['image_path']); ?>" alt="" class="rounded" style="max-width:160px;"></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <span class="mi mi-leading"><?= $vehicle ? 'save' : 'add'; ?></span><?= $vehicle ? 'Save changes' : 'Add vehicle'; ?>
                        </button>
                        <a href="<?= !empty($next) ? site_url($next === 'packages' ? 'packages' : ($next === 'booking/vehicle' ? 'booking/vehicle' : $next)) : site_url('vehicles'); ?>" class="btn btn-link">Cancel</a>
                    </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>
