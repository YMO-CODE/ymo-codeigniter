<?php defined('BASEPATH') OR exit('No direct script access allowed');
$draft = (array) $this->session->flashdata('gbp_post_draft');
$sel = isset($draft['workshops']) ? (array) $draft['workshops'] : array_keys($connected);
$status_badge = array(
    'scheduled' => array('Scheduled', 'bg-info text-dark'),
    'published' => array('Live', 'bg-success'),
    'partial'   => array('Partly live', 'bg-warning text-dark'),
    'failed'    => array('Failed', 'bg-danger'),
);
$cta_label = array('CALL' => 'Call now', 'BOOK' => 'Book', 'LEARN_MORE' => 'Learn more', 'NONE' => 'No button');
?>
<style>
.gp-item { border: 1px solid #e5e7eb; border-radius: 10px; padding: .75rem .9rem; margin-bottom: .6rem; background: #fff; }
.gp-thumb { width: 84px; height: 63px; object-fit: cover; border-radius: 6px; flex: 0 0 auto; }
.gp-count { font-size: .8rem; }
</style>

<?php if (!$google_on): ?>
<div class="alert alert-warning small">Google isn't connected yet. Connect it on the <a href="<?= admin_url('reviews#setup'); ?>">Google reviews</a> page first.</div>
<?php elseif (!$connected): ?>
<div class="alert alert-warning small">No workshop listings are selected. Choose them on the <a href="<?= admin_url('reviews#setup'); ?>">Google reviews</a> page.</div>
<?php endif; ?>

<div class="row g-3">
<div class="col-lg-5">
    <div class="ymo-card p-3 mb-3">
        <h2 class="h6 mb-1">New post</h2>
        <p class="small ymo-muted mb-2">Shows on your Google listing in Search and Maps ("Updates"). Aim for one a week per workshop<?= $last ? ' · last post '.html_escape(date('d M', strtotime($last))) : ''; ?>.</p>
        <?php if ($can_edit): ?>
        <?= form_open_multipart(admin_url('google-posts/save'), array('id' => 'gp-form')); ?>
            <label class="small fw-semibold mb-1" for="gp-text">Text</label>
            <textarea id="gp-text" name="summary" class="form-control form-control-sm mb-1" rows="7" maxlength="<?= (int) $max_text; ?>" required
                placeholder="e.g. Monsoon check-up: free 25-point inspection with every service this week. Brakes, wipers, tyres and AC checked, with WhatsApp photo updates of everything we find."><?= html_escape(isset($draft['summary']) ? $draft['summary'] : ''); ?></textarea>
            <div class="d-flex justify-content-between gp-count ymo-muted mb-2"><span>No phone numbers in the text (Google rejects them) – use the Call button.</span><span id="gp-n">0/<?= (int) $max_text; ?></span></div>

            <label class="small fw-semibold mb-1" for="gp-photo">Photo <span class="ymo-muted fw-normal">(real workshop photo, no text on it)</span></label>
            <input id="gp-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm mb-2">

            <div class="row g-2 mb-2">
                <div class="col-6">
                    <label class="small fw-semibold mb-1" for="gp-cta">Button</label>
                    <select id="gp-cta" name="cta_type" class="form-select form-select-sm">
                        <?php foreach ($cta_label as $k => $v): ?>
                            <option value="<?= $k; ?>" <?= (isset($draft['cta_type']) ? $draft['cta_type'] : 'CALL') === $k ? 'selected' : ''; ?>><?= $v; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <span class="small fw-semibold d-block mb-1">Workshops</span>
                    <?php foreach ($workshops as $slug => $w): ?>
                        <label class="small d-inline-flex align-items-center gap-1 me-2">
                            <input type="checkbox" name="workshops[]" value="<?= html_escape($slug); ?>" <?= in_array($slug, $sel, TRUE) ? 'checked' : ''; ?> <?= isset($connected[$slug]) ? '' : 'disabled'; ?>>
                            <?= html_escape($w['label']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-3 align-items-center small mb-3">
                <label class="d-flex gap-1 align-items-center"><input type="radio" name="when" value="now" checked> Post now</label>
                <label class="d-flex gap-1 align-items-center"><input type="radio" name="when" value="date"> Schedule for</label>
                <input type="date" name="publish_on" class="form-control form-control-sm w-auto" min="<?= date('Y-m-d'); ?>" value="<?= html_escape(isset($draft['publish_on']) && $draft['publish_on'] ? $draft['publish_on'] : date('Y-m-d', strtotime('+1 day'))); ?>">
            </div>
            <button class="btn btn-primary btn-sm" <?= $connected ? '' : 'disabled'; ?>><span class="mi mi-sm mi-leading">send</span>Save post</button>
            <p class="small ymo-muted mt-2 mb-0">Book / Learn more open that workshop's page on the website (tracked as <em>gbp-post</em> in analytics). Scheduled posts go live with the 9 AM daily run.</p>
        <?= form_close(); ?>
        <?php else: ?>
            <p class="small mb-0">You can view posts but not create them.</p>
        <?php endif; ?>
    </div>
</div>

<div class="col-lg-7">
    <div class="ymo-card p-3 mb-3">
        <h2 class="h6 mb-2">Posts</h2>
        <?php if (!$posts): ?><p class="small ymo-muted mb-0">No posts yet.</p><?php endif; ?>
        <?php foreach ($posts as $p):
            $res = json_decode((string) $p['results'], TRUE) ?: array();
            $b = isset($status_badge[$p['status']]) ? $status_badge[$p['status']] : array($p['status'], 'bg-light');
        ?>
            <div class="gp-item">
                <div class="d-flex gap-2">
                    <?php if (!empty($p['has_image'])): ?>
                        <img class="gp-thumb" src="<?= html_escape($this->gbp_post_service->image_url($p)); ?>" alt="">
                    <?php endif; ?>
                    <div class="flex-grow-1 small">
                        <div class="d-flex justify-content-between flex-wrap gap-1 mb-1">
                            <span><span class="badge <?= $b[1]; ?>"><?= $b[0]; ?></span>
                                <span class="ymo-muted"><?= $p['status'] === 'scheduled' ? 'goes live '.html_escape(date('D d M', strtotime($p['publish_on']))) : ($p['published_at'] ? html_escape(date('d M, H:i', strtotime($p['published_at']))) : ''); ?> · <?= html_escape($cta_label[$p['cta_type']] ?? $p['cta_type']); ?></span></span>
                            <span class="ymo-muted"><?php foreach (array_filter(explode(',', $p['workshops'])) as $w):
                                $r = isset($res[$w]) ? $res[$w] : NULL;
                                $lbl = isset($workshops[$w]) ? $workshops[$w]['label'] : $w;
                                if ($r && !empty($r['ok']) && !empty($r['url'])) {
                                    echo '<a href="'.html_escape($r['url']).'" target="_blank" rel="noopener">'.html_escape($lbl).' ↗</a> ';
                                } elseif ($r && empty($r['ok'])) {
                                    echo '<span class="text-danger" title="'.html_escape((string) $r['error']).'">'.html_escape($lbl).' ✕</span> ';
                                } else {
                                    echo html_escape($lbl).' ';
                                }
                            endforeach; ?></span>
                        </div>
                        <div style="white-space:pre-wrap"><?= html_escape(mb_strimwidth($p['summary'], 0, 300, '…')); ?></div>
                        <?php foreach ($res as $w => $r): if (empty($r['ok']) && !empty($r['error'])): ?>
                            <div class="text-danger mt-1"><?= html_escape((isset($workshops[$w]) ? $workshops[$w]['label'] : $w).': '.$r['error']); ?></div>
                        <?php endif; endforeach; ?>
                        <?php if ($can_edit && $p['status'] !== 'published'): ?>
                        <div class="d-flex gap-2 mt-2">
                            <?= form_open(admin_url('google-posts/'.(int) $p['id'].'/publish')); ?><button class="btn btn-sm btn-outline-primary"><?= $p['status'] === 'scheduled' ? 'Post now' : 'Retry'; ?></button><?= form_close(); ?>
                            <?php if (in_array($p['status'], array('scheduled', 'failed'), TRUE)): ?>
                            <?= form_open(admin_url('google-posts/'.(int) $p['id'].'/delete')); ?><button class="btn btn-sm btn-link text-danger">Remove</button><?= form_close(); ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</div>
<script>
(function () {
  var t = document.getElementById('gp-text'), n = document.getElementById('gp-n');
  if (!t || !n) { return; }
  var f = function () { n.textContent = t.value.length + '/' + t.maxLength; };
  t.addEventListener('input', f); f();
  var d = document.querySelector('input[name=publish_on]');
  if (d) { d.addEventListener('focus', function () { document.querySelector('input[name=when][value=date]').checked = true; }); }
})();
</script>
