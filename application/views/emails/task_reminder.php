<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<p>Hi <?= html_escape($task['assignee_name']); ?>,</p>
<p>You have a CRM follow-up due soon:</p>
<p><strong><?= html_escape($task['title']); ?></strong><br>
Due: <?= html_escape(date('d M Y, h:i A', strtotime($task['due_at']))); ?></p>
<?php if (!empty($task['notes'])): ?>
<p><?= nl2br(html_escape($task['notes'])); ?></p>
<?php endif; ?>
<p>- YMO CRM</p>
