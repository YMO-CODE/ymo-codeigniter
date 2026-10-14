<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
if (empty($roll_items) || !is_array($roll_items)) {
    return;
}
$roll_lead = isset($roll_lead) ? (string) $roll_lead : '';
$roll_loop = !empty($roll_loop);
?>
<div class="ymo-v2-roll-list" data-roll-loop="<?= $roll_loop ? '1' : '0'; ?>">
    <?php if ($roll_lead !== ''): ?>
        <p class="ymo-v2-roll-list__lead"><?= html_escape($roll_lead); ?></p>
    <?php endif; ?>
    <div class="ymo-v2-roll-list__viewport">
        <ul class="ymo-v2-roll-list__track">
            <?php foreach ($roll_items as $item): ?>
                <?php
                if (empty($item['href']) || empty($item['label'])) {
                    continue;
                }
                $aria = isset($item['aria']) ? (string) $item['aria'] : (string) $item['label'];
                ?>
                <li class="ymo-v2-roll-list__item">
                    <a href="<?= html_escape($item['href']); ?>" aria-label="<?= html_escape($aria); ?>">
                        <span class="ymo-v2-roll-list__text"><?= html_escape((string) $item['label']); ?>.</span>
                    </a>
                </li>
            <?php endforeach; ?>
            <?php if ($roll_loop): ?>
                <?php foreach ($roll_items as $item): ?>
                    <?php
                    if (empty($item['href']) || empty($item['label'])) {
                        continue;
                    }
                    $aria = isset($item['aria']) ? (string) $item['aria'] : (string) $item['label'];
                    ?>
                    <li class="ymo-v2-roll-list__item" aria-hidden="true">
                        <a href="<?= html_escape($item['href']); ?>" tabindex="-1">
                            <span class="ymo-v2-roll-list__text"><?= html_escape((string) $item['label']); ?>.</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>
