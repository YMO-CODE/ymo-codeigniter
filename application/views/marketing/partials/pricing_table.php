<?php defined('BASEPATH') OR exit('No direct script access allowed');
if (empty($pricing_tiers) || !is_array($pricing_tiers)) {
    return;
}
?>
<div class="ymo-content-section mb-5">
    <h2 class="md-headline-md mb-3">Transparent pricing</h2>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-2">
            <thead class="table-light">
                <tr>
                    <th scope="col">Car type / variant</th>
                    <th scope="col" class="text-end">Price (from)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pricing_tiers as $tier): ?>
                    <?php if (empty($tier['label'])) { continue; } ?>
                    <tr>
                        <td><?= html_escape($tier['label']); ?></td>
                        <td class="text-end"><?= html_escape($tier['price'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="md-body-md ymo-muted mb-0">Final price confirmed before service begins. No hidden charges.</p>
</div>
