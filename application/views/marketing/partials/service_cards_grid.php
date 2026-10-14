<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (empty($services) || !is_array($services)) { return; } ?>

<?php
$orbit_count = count($services);
$orbit_ring  = $orbit_count >= 4;
?>
<div class="ymo-v2-card-orbit-viewport<?= $orbit_ring ? ' ymo-v2-card-orbit-viewport--active' : ''; ?>">
    <div class="row g-4 ymo-v2-card-grid ymo-service-catalog<?= $orbit_ring ? ' ymo-v2-card-grid--orbit' : ''; ?>"<?= $orbit_ring ? ' style="--orbit-count: '.(int) $orbit_count.';"' : ''; ?>>
        <?php foreach ($services as $i => $svc): ?>
            <div class="col-md-6 col-lg-4 d-flex"<?= $orbit_ring ? ' style="--orbit-i: '.(int) $i.';"' : ''; ?>>
                <?php $this->load->view('marketing/partials/service_card_link', array('svc' => $svc)); ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
