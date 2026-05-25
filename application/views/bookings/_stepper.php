<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$steps = array(
    1 => array('label' => 'Package',  'icon' => 'build'),
    2 => array('label' => 'Vehicle',  'icon' => 'directions_car'),
    3 => array('label' => 'Details',  'icon' => 'edit_note'),
    4 => array('label' => 'Confirm',  'icon' => 'check_circle'),
);
$current = isset($step) ? (int) $step : 1;
?>
<ol class="ymo-stepper">
    <?php foreach ($steps as $n => $meta):
        $is_done   = $n < $current;
        $is_active = $n === $current;
    ?>
        <li class="<?= $is_done ? 'is-done' : ($is_active ? 'is-active' : ''); ?>">
            <span class="mi mi-sm mi-leading"><?= $is_done ? 'check' : $meta['icon']; ?></span>
            <?= sprintf('Step %d', $n); ?> · <?= html_escape($meta['label']); ?>
        </li>
    <?php endforeach; ?>
</ol>
