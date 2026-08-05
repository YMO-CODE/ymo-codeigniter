<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$action = $offer ? admin_url('offers/'.$offer['id'].'/edit') : admin_url('offers/new');
$default_cta = site_url('packages');
?>
<a href="<?= admin_url('offers'); ?>" class="small">
    <span class="mi mi-sm mi-leading">arrow_back</span>All offers
</a>

<div class="ymo-card mt-3">
    <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

    <?= form_open_multipart($action); ?>
        <div class="row g-3">
            <div class="col-md-8">
                <div class="form-floating">
                    <input class="form-control" id="of_title" name="title" placeholder=" "
                           value="<?= set_value('title', $offer['title'] ?? ''); ?>" required>
                    <label for="of_title">Title</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <input class="form-control" id="of_sort" name="sort_order" type="number" placeholder=" "
                           value="<?= set_value('sort_order', isset($offer['sort_order']) ? $offer['sort_order'] : 100); ?>">
                    <label for="of_sort">Sort</label>
                </div>
            </div>
            <div class="col-12">
                <div class="form-floating">
                    <textarea class="form-control" id="of_body" name="body" placeholder=" " rows="4"
                              style="height:120px;" required><?= set_value('body', $offer['body'] ?? ''); ?></textarea>
                    <label for="of_body">Message (shown in popup)</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-floating">
                    <input class="form-control" id="of_cta_label" name="cta_label" placeholder=" "
                           value="<?= set_value('cta_label', $offer['cta_label'] ?? 'Book now'); ?>">
                    <label for="of_cta_label">Button label (optional)</label>
                </div>
            </div>
            <div class="col-md-8">
                <div class="form-floating">
                    <input class="form-control" id="of_cta_url" name="cta_url" type="url" placeholder=" "
                           value="<?= set_value('cta_url', $offer['cta_url'] ?? $default_cta); ?>">
                    <label for="of_cta_url">Button URL</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating">
                    <input class="form-control" id="of_starts" name="starts_at" type="datetime-local" placeholder=" "
                           value="<?= set_value('starts_at', !empty($offer['starts_at']) ? date('Y-m-d\TH:i', strtotime($offer['starts_at'])) : ''); ?>">
                    <label for="of_starts">Starts at (optional)</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-floating">
                    <input class="form-control" id="of_ends" name="ends_at" type="datetime-local" placeholder=" "
                           value="<?= set_value('ends_at', !empty($offer['ends_at']) ? date('Y-m-d\TH:i', strtotime($offer['ends_at'])) : ''); ?>">
                    <label for="of_ends">Ends at (optional)</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label small ymo-muted" for="of_image">Banner image (optional)</label>
                <input class="form-control" id="of_image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                <?php if (!empty($offer['image_path'])): ?>
                    <div class="mt-2">
                        <img src="<?= base_url($offer['image_path']); ?>" alt="" class="rounded" style="max-height:120px;">
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="remove_image" name="remove_image" value="1">
                            <label class="form-check-label" for="remove_image">Remove current image</label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                        <?= !empty($offer['is_active']) || !$offer ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">Active (show on site)</label>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-primary" type="submit">
                <span class="mi mi-leading">save</span>Save
            </button>
            <a href="<?= admin_url('offers'); ?>" class="btn btn-link">Cancel</a>
            <?php if (!empty($offer['id'])): ?>
            <a href="<?= html_escape(rtrim(ymo_booking_url(''), '/').'?ymo_offer_preview='.(int) $offer['id']); ?>" class="btn btn-outline-secondary" target="_blank" rel="noopener">
                <span class="mi mi-leading">open_in_new</span>Preview popup
            </a>
            <?php endif; ?>
        </div>
    <?= form_close(); ?>
</div>
