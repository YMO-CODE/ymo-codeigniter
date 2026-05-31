<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$ci = &get_instance();

/* Flash messages render as Material 3 snackbars in a fixed bottom-centre
   host. Auto-dismiss is handled in ymo.js (default 6s), and each variant
   gets a leading icon. The host always renders so server-side flashes and
   future client-side ones can share it. */
$flash_types = array(
    'success' => array('class' => 'is-success', 'icon' => 'check_circle'),
    'error'   => array('class' => 'is-error',   'icon' => 'error'),
    'danger'  => array('class' => 'is-error',   'icon' => 'error'),
    'warning' => array('class' => 'is-warning', 'icon' => 'warning'),
    'info'    => array('class' => 'is-info',    'icon' => 'info'),
);
?>
<div class="md-snackbar-host" id="md-snackbar-host" aria-live="polite" aria-atomic="true">
<?php foreach ($flash_types as $key => $meta):
    $msg = $ci->session->flashdata($key);
    if (empty($msg)) { continue; }
?>
    <div class="md-snackbar <?= $meta['class']; ?>" role="status" data-ttl="6000">
        <span class="mi" aria-hidden="true"><?= $meta['icon']; ?></span>
        <span class="md-snackbar-msg"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></span>
        <button type="button" class="md-snackbar-close" aria-label="Dismiss">
            <span class="mi mi-sm">close</span>
        </button>
    </div>
<?php endforeach; ?>
</div>
