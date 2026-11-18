<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$row = function (array $t, $late) {
    $when = $late ? date('d M', strtotime($t['due_at'])) : date('h:i A', strtotime($t['due_at']));
    $link = $t['lead_id'] ? admin_url('leads/'.(int) $t['lead_id'].'#log') : admin_url('tasks');
    $tel = $t['mobile'] ? ' &middot; <a href="tel:'.htmlspecialchars(preg_replace('/[^+\d]/', '', $t['mobile'])).'" style="color:#3a6f37;">'.htmlspecialchars($t['mobile']).'</a>' : '';
    $note = trim((string) $t['notes']) !== '' ? '<br><span style="color:#6b7280;font-size:13px;">'.htmlspecialchars(mb_strimwidth($t['notes'], 0, 140, '…')).'</span>' : '';
    return '<tr><td style="padding:8px 0;border-bottom:1px solid #eef0f3;width:70px;color:'.($late ? '#b42318' : '#6b7280').';font-size:13px;vertical-align:top;">'.$when.'</td>'
        .'<td style="padding:8px 0;border-bottom:1px solid #eef0f3;"><a href="'.htmlspecialchars($link).'" style="color:#1d2026;font-weight:bold;text-decoration:none;">'
        .($t['priority'] ? '&#9733; ' : '').htmlspecialchars($t['title']).'</a>'.$tel.$note.'</td></tr>';
};

$body = '<h2 style="margin:0 0 4px;font-size:18px;">Hi '.htmlspecialchars(strtok((string) $admin['name'], ' ') ?: 'team').'</h2>'
    .'<p style="margin:0 0 16px;color:#6b7280;">Your CRM summary for '.date('l, d M').'.</p>';

$body .= '<table cellpadding="0" cellspacing="0" style="width:100%;margin-bottom:18px;"><tr>';
foreach (array(
    array(count($due_today), 'calls today'),
    array(count($overdue), 'overdue'),
    array((int) $team['new_leads'], 'new leads (24h)'),
    array((int) $team['unread'], 'unread WhatsApp'),
    array((int) $team['pending_bookings'], 'bookings to confirm'),
) as $k) {
    $body .= '<td style="text-align:center;padding:8px 4px;background:#f4f5f7;border:3px solid #fff;"><div style="font-size:20px;font-weight:bold;">'.$k[0].'</div><div style="font-size:12px;color:#6b7280;">'.$k[1].'</div></td>';
}
$body .= '</tr></table>';

if ($overdue) {
    $body .= '<h3 style="margin:0 0 4px;font-size:15px;color:#b42318;">Overdue</h3><table cellpadding="0" cellspacing="0" style="width:100%;margin-bottom:16px;font-size:14px;">';
    foreach (array_slice($overdue, 0, 30) as $t) { $body .= $row($t, TRUE); }
    $body .= '</table>'.(count($overdue) > 30 ? '<p style="font-size:13px;color:#6b7280;">…and '.(count($overdue) - 30).' more.</p>' : '');
}
if ($due_today) {
    $body .= '<h3 style="margin:0 0 4px;font-size:15px;">Today</h3><table cellpadding="0" cellspacing="0" style="width:100%;margin-bottom:16px;font-size:14px;">';
    foreach (array_slice($due_today, 0, 40) as $t) { $body .= $row($t, FALSE); }
    $body .= '</table>'.(count($due_today) > 40 ? '<p style="font-size:13px;color:#6b7280;">…and '.(count($due_today) - 40).' more.</p>' : '');
}
if (!$overdue && !$due_today) {
    $body .= '<p>No follow-up calls for you today.</p>';
}
$body .= '<p><a href="'.htmlspecialchars(admin_url('dashboard')).'" style="background:#3a6f37;color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;display:inline-block;">Open Today</a></p>'
    .'<p style="font-size:12px;color:#6b7280;">&#9733; = high priority (for example, asked to book). You get an instant email only when someone asks to book; everything else is in this daily summary.</p>';

include __DIR__.'/_layout.php';
