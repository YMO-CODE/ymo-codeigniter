<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** Tabs shared by the Messages section: campaigns and WhatsApp templates. */
$active = isset($active) ? $active : 'campaigns';
?>
<div class="ymo-tabs mb-3">
    <a href="<?= admin_url('campaigns'); ?>" class="<?= $active === 'campaigns' ? 'active' : ''; ?>">Campaigns</a>
    <a href="<?= admin_url('whatsapp-templates'); ?>" class="<?= $active === 'templates' ? 'active' : ''; ?>">WhatsApp templates</a>
</div>
