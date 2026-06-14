<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Import customers from CSV</h2>
            <a href="<?= admin_url('customers'); ?>" class="btn btn-outline-secondary btn-sm">Back to customers</a>
        </div>

        <?php if (!$preview): ?>
        <div class="ymo-card mb-3">
            <p class="text-muted small mb-3">
                Upload a CSV with columns: <strong>name</strong>, mobile, email, workshop, notes, tags.
                Tags can be comma-separated in one cell. Use <strong>merge notes</strong> to append visit history without overwriting existing contacts.
                <a href="<?= admin_url('customers/import/template'); ?>">Download CSV template</a>
            </p>
            <?= form_open_multipart(admin_url('customers/import/preview')); ?>
            <div class="mb-3">
                <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
            </div>
            <button class="btn btn-primary">Upload &amp; preview</button>
            <?= form_close(); ?>
        </div>
        <div class="ymo-card">
            <h3 class="h6">YMO customer Excel</h3>
            <p class="small text-muted mb-2">
                To import the legacy customer workbook, run the merge script locally first:
            </p>
            <pre class="small bg-light p-2 rounded mb-0">python import/scripts/merge_ymo_customer_excel.py "import/source/YMO Customer data sheet.xlsx"</pre>
            <p class="small text-muted mt-2 mb-0">
                Then upload <code>import/staging/contacts_master.csv</code> here.
            </p>
        </div>
        <?php else: ?>
        <div class="ymo-card mb-3">
            <div class="row g-3 mb-3">
                <div class="col-md-3"><div class="border rounded p-2 text-center"><div class="h4 mb-0"><?= (int) $preview['total']; ?></div><div class="small text-muted">Rows</div></div></div>
                <div class="col-md-3"><div class="border rounded p-2 text-center"><div class="h4 mb-0 text-success"><?= (int) $preview['new']; ?></div><div class="small text-muted">New</div></div></div>
                <div class="col-md-3"><div class="border rounded p-2 text-center"><div class="h4 mb-0 text-warning"><?= (int) $preview['duplicate']; ?></div><div class="small text-muted">Duplicates</div></div></div>
            </div>

            <?= form_open(admin_url('customers/import/commit')); ?>
            <div class="mb-3">
                <label class="form-label">When a customer already exists (same mobile or email)</label>
                <select name="duplicate_policy" class="form-select">
                    <option value="merge_notes" selected>Merge — append notes, keep existing fields, add tags</option>
                    <option value="skip">Skip duplicates</option>
                    <option value="update">Update — replace fields and tags from CSV</option>
                </select>
            </div>
            <button class="btn btn-primary" onclick="return confirm('Import <?= (int) $preview['total']; ?> customers into CRM?');">Import now</button>
            <a href="<?= admin_url('customers/import'); ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            <?= form_close(); ?>
        </div>

        <div class="ymo-card p-0">
            <table class="ymo-table mb-0">
                <thead><tr><th>Name</th><th>Mobile</th><th>Email</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($preview['sample'] as $s): ?>
                    <tr>
                        <td><?= html_escape($s['row']['name']); ?></td>
                        <td class="small"><?= html_escape($s['row']['mobile']); ?></td>
                        <td class="small"><?= html_escape($s['row']['email']); ?></td>
                        <td class="small">
                            <?php if ($s['existing']): ?>
                                <span class="text-warning">Duplicate → #<?= (int) $s['existing']['id']; ?></span>
                            <?php else: ?>
                                <span class="text-success">New</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($preview['total'] > 15): ?>
                <p class="small text-muted p-2 mb-0">Showing first 15 of <?= (int) $preview['total']; ?> rows.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
