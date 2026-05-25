<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><span class="mi mi-leading">directions_car</span>My vehicles</h1>
        <a href="<?= site_url('vehicles/new'); ?>" class="btn btn-primary">
            <span class="mi mi-leading">add</span>Add vehicle
        </a>
    </div>

    <?php if (empty($vehicles)): ?>
        <div class="md-card-elevated text-center py-5">
            <span class="mi mi-xl" style="color:var(--ymo-grey-500);">no_crash</span>
            <h5 class="mb-2 mt-2">No vehicles yet.</h5>
            <p class="ymo-muted">Add your first car to start booking services.</p>
            <a href="<?= site_url('vehicles/new'); ?>" class="btn btn-primary">
                <span class="mi mi-leading">add</span>Add your first vehicle
            </a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($vehicles as $v): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="md-card-elevated h-100">
                        <?php if (!empty($v['image_path'])): ?>
                            <img src="<?= base_url($v['image_path']); ?>" alt="" class="img-fluid rounded mb-3" style="aspect-ratio:16/9;object-fit:cover;width:100%;">
                        <?php else: ?>
                            <div class="rounded mb-3 d-flex align-items-center justify-content-center"
                                 style="aspect-ratio:16/9;background:var(--ymo-grey-100);">
                                <span class="mi mi-xl" style="color:var(--ymo-grey-300);">directions_car</span>
                            </div>
                        <?php endif; ?>
                        <h5 class="mb-1"><?= html_escape($v['make_name']); ?> <?= html_escape($v['variant']); ?></h5>
                        <p class="ymo-muted small mb-3 font-monospace"><?= html_escape($v['vehicle_number']); ?></p>
                        <div class="d-flex gap-2">
                            <a href="<?= site_url('vehicles/'.$v['id'].'/edit'); ?>" class="btn btn-sm btn-outline-primary">
                                <span class="mi mi-sm mi-leading">edit</span>Edit
                            </a>
                            <?= form_open(site_url('vehicles/'.$v['id'].'/delete'), array('class' => 'd-inline')); ?>
                                <button class="btn btn-sm btn-outline-danger" data-confirm="Remove this vehicle?">
                                    <span class="mi mi-sm mi-leading">delete</span>Remove
                                </button>
                            <?= form_close(); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
