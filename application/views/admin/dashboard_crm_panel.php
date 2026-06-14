        <?php if (!empty($summary['crm']) && function_exists('crm_can') && crm_can('tasks.view')): ?>
        <div class="md-card-elevated mt-3">
            <p class="ymo-muted text-uppercase small mb-2">Today's CRM actions</p>
            <ul class="list-unstyled small mb-2">
                <li>Follow-ups today: <strong><?= (int) $summary['crm']['tasks_today']; ?></strong></li>
                <li>Hot leads: <strong><?= (int) $summary['crm']['hot_leads']; ?></strong></li>
                <li>Next week callbacks: <strong><?= count($summary['crm']['week_callbacks']); ?></strong></li>
                <li>Next month callbacks: <strong><?= count($summary['crm']['month_callbacks']); ?></strong></li>
                <li>Service due: <strong><?= (int) $summary['crm']['service_due_count']; ?></strong></li>
            </ul>
            <?php if (!empty($summary['crm']['tasks_today_list'])): ?>
                <p class="small fw-semibold mb-1">Today's follow-ups</p>
                <ul class="list-unstyled small mb-2">
                <?php foreach ($summary['crm']['tasks_today_list'] as $t): ?>
                    <li><a href="<?= admin_url('tasks/'.$t['id'].'/edit'); ?>"><?= html_escape($t['title']); ?></a></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if (!empty($summary['crm']['hot_leads_list'])): ?>
                <p class="small fw-semibold mb-1">Pending hot leads</p>
                <ul class="list-unstyled small mb-2">
                <?php foreach ($summary['crm']['hot_leads_list'] as $l): ?>
                    <li><a href="<?= admin_url('leads/'.$l['id']); ?>"><?= html_escape($l['name']); ?></a></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if (!empty($summary['crm']['service_due'])): ?>
                <p class="small fw-semibold mb-1">Service due customers</p>
                <ul class="list-unstyled small mb-2">
                <?php foreach ($summary['crm']['service_due'] as $c): ?>
                    <li><a href="<?= admin_url('customers/'.$c['id']); ?>"><?= html_escape($c['name']); ?></a></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <a href="<?= admin_url('tasks?due=today'); ?>" class="btn btn-outline-primary btn-sm me-1">Tasks</a>
            <a href="<?= admin_url('leads?stage=hot_lead'); ?>" class="btn btn-outline-primary btn-sm me-1">Hot leads</a>
            <?php if (crm_can('reports.view')): ?>
                <a href="<?= admin_url('reports'); ?>" class="btn btn-outline-secondary btn-sm">Reports</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
