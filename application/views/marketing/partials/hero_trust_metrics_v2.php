<?php defined('BASEPATH') OR exit('No direct script access allowed');
$cfg = function_exists('marketing_trust_config') ? marketing_trust_config() : array();
if (empty($cfg['show_in_hero'])) {
    return;
}

$items = array();
$rating = isset($cfg['google_rating']) ? (float) $cfg['google_rating'] : 0;
$reviews = isset($cfg['review_count']) ? (int) $cfg['review_count'] : 0;
$years = isset($cfg['years']) ? (int) $cfg['years'] : 0;
$cities = isset($cfg['cities']) ? (int) $cfg['cities'] : 0;
$review_label = !empty($cfg['review_label']) ? (string) $cfg['review_label'] : 'Google reviews';

if ($rating > 0) {
    $items[] = array(
        'icon'  => 'star',
        'value' => number_format($rating, 1),
        'label' => 'Google rating',
    );
}
if ($reviews > 0) {
    $items[] = array(
        'icon'  => 'reviews',
        'value' => number_format($reviews).'+',
        'label' => $review_label,
    );
}
if ($years > 0) {
    $items[] = array(
        'icon'  => 'history',
        'value' => $years.'+ years',
        'label' => 'Serving drivers',
    );
}
if ($cities > 0) {
    $items[] = array(
        'icon'  => 'location_city',
        'value' => (string) $cities,
        'label' => $cities === 1 ? 'City' : 'Service cities',
    );
}

if ($items === array()) {
    return;
}

$count_class = 'ymo-v2-hero-metrics--count-'.count($items);
?>
<div class="ymo-v2-hero-metrics <?= html_escape($count_class); ?>" aria-label="Customer ratings">
    <?php foreach ($items as $item): ?>
        <div class="ymo-v2-hero-metric">
            <span class="ymo-v2-hero-metric__icon mi" aria-hidden="true"><?= html_escape($item['icon']); ?></span>
            <div class="ymo-v2-hero-metric__body">
                <span class="ymo-v2-hero-metric__value"><?= html_escape($item['value']); ?></span>
                <span class="ymo-v2-hero-metric__label"><?= html_escape($item['label']); ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>
