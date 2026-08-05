<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<form class="ymo-card mb-3" method="get">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="small">From</label><input type="date" name="from" class="form-control" value="<?= html_escape($from); ?>"></div>
        <div class="col-md-3"><label class="small">To</label><input type="date" name="to" class="form-control" value="<?= html_escape($to); ?>"></div>
        <div class="col-md-2"><button class="btn btn-primary">Apply</button></div>
        <div class="col-md-4 text-end">
            <a href="<?= admin_url('reports/export/leads'); ?>" class="btn btn-outline-secondary btn-sm">Export leads</a>
            <a href="<?= admin_url('customers/export'); ?>" class="btn btn-outline-secondary btn-sm">Export customers</a>
            <a href="<?= admin_url('reports/export/revenue?from='.urlencode($from).'&to='.urlencode($to)); ?>" class="btn btn-outline-secondary btn-sm">Export revenue</a>
            <a href="<?= admin_url('reports/export/service-due'); ?>" class="btn btn-outline-secondary btn-sm">Export service due</a>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="md-card-elevated"><p class="ymo-muted small mb-1">Total leads</p><h3><?= (int) $conversion['total']; ?></h3></div></div>
    <div class="col-md-3"><div class="md-card-elevated"><p class="ymo-muted small mb-1">Converted</p><h3><?= (int) $conversion['converted']; ?></h3></div></div>
    <div class="col-md-3"><div class="md-card-elevated"><p class="ymo-muted small mb-1">Conversion rate</p><h3><?= html_escape($conversion['rate']); ?>%</h3></div></div>
    <div class="col-md-3"><div class="md-card-elevated"><p class="ymo-muted small mb-1">Revenue (invoices)</p><h3>₹<?= number_format((float) ($revenue['total'] ?? 0), 0); ?></h3></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="ymo-card">
            <h6>Locality / workshop</h6>
            <table class="ymo-table"><thead><tr><th>Workshop</th><th>Customers</th><th>Leads</th></tr></thead>
            <tbody>
            <?php foreach ($locality as $loc): ?>
                <tr><td><?= html_escape($loc['locality']); ?></td><td><?= (int) $loc['customers']; ?></td><td><?= (int) $loc['leads']; ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="ymo-card">
            <h6>Revenue by workshop</h6>
            <table class="ymo-table"><thead><tr><th>Workshop</th><th>Revenue</th><th>Invoices</th></tr></thead>
            <tbody>
            <?php foreach ($revenue['by_workshop'] as $r): ?>
                <tr><td><?= html_escape($r['locality']); ?></td><td>₹<?= number_format((float) $r['revenue'], 0); ?></td><td><?= (int) $r['invoices']; ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="ymo-card">
            <h6>Service due customers</h6>
            <table class="ymo-table"><thead><tr><th>Name</th><th>Workshop</th><th>Due</th></tr></thead>
            <tbody>
            <?php foreach ($service_due as $c): ?>
                <tr>
                    <td><a href="<?= admin_url('customers/'.$c['id']); ?>"><?= html_escape($c['name']); ?></a></td>
                    <td class="small"><?= html_escape($c['company']); ?></td>
                    <td class="small"><?= !empty($c['due_at']) ? html_escape(date('d M Y', strtotime($c['due_at']))) : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="ymo-card">
            <h6>Lead sources</h6>
            <table class="ymo-table"><thead><tr><th>Source</th><th>Leads</th><th>Converted</th></tr></thead>
            <tbody>
            <?php foreach ($sources as $s): ?>
                <tr><td><?= html_escape($s['label']); ?></td><td><?= (int) $s['total']; ?></td><td><?= (int) $s['converted']; ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="ymo-card">
            <h6>Team performance</h6>
            <table class="ymo-table"><thead><tr><th>Member</th><th>Leads</th><th>Converted</th><th>Tasks done</th></tr></thead>
            <tbody>
            <?php foreach ($team as $m): ?>
                <tr><td><?= html_escape($m['name']); ?></td><td><?= (int) $m['leads']; ?></td><td><?= (int) $m['converted']; ?></td><td><?= (int) $m['tasks_done']; ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="ymo-card">
            <h6>Campaign performance</h6>
            <table class="ymo-table"><thead><tr><th>Campaign</th><th>Sent</th><th>Failed</th></tr></thead>
            <tbody>
            <?php foreach ($campaigns as $c): ?>
                <tr><td class="small"><?= html_escape($c['name']); ?></td><td><?= (int) $c['sent']; ?></td><td><?= (int) $c['failed']; ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
    </div>
    <?php if ($webhooks): ?>
    <div class="col-lg-6">
        <div class="ymo-card">
            <h6>Recent webhook activity</h6>
            <table class="ymo-table"><thead><tr><th>Provider</th><th>Event</th><th>Time</th></tr></thead>
            <tbody>
            <?php foreach ($webhooks as $w): ?>
                <tr><td><?= html_escape($w['provider']); ?></td><td class="small"><?= html_escape($w['event_type']); ?></td><td class="small"><?= html_escape(date('d M H:i', strtotime($w['created_at']))); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <p class="small ymo-muted mt-2 mb-0">
                Webhook URLs (POST unless Meta verify GET):<br>
                Meta: <code><?= html_escape(site_url('api/webhooks/meta')); ?></code> - Lead Forms (<code>leadgen</code>) + Instagram DMs (<code>messages</code>)<br>
                Website: <code><?= html_escape(site_url('api/webhooks/website')); ?></code> - header <code>X-CRM-Signature: sha256=…</code><br>
                WhatsApp: <code><?= html_escape(site_url('api/webhooks/whatsapp')); ?></code>
            </p>
        </div>
    </div>
    <?php elseif (crm_can('reports.view')): ?>
    <div class="col-lg-12">
        <div class="ymo-card">
            <h6>Integration endpoints</h6>
            <p class="small ymo-muted mb-0">
                Meta: <code><?= html_escape(site_url('api/webhooks/meta')); ?></code> ·
                Website: <code><?= html_escape(site_url('api/webhooks/website')); ?></code> ·
                WhatsApp: <code><?= html_escape(site_url('api/webhooks/whatsapp')); ?></code>
            </p>
        </div>
    </div>
    <?php endif; ?>
</div>
