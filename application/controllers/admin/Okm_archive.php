<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Setup → OkMechanic archive: read-only view of what came over from OkMechanic and has no
 * screen of its own yet (estimates, purchases, expenses, suppliers, staff, accounts) plus
 * the original record behind every imported job card.
 */
class Okm_archive extends Admin_Controller
{
    const TYPES = array(
        'estimate'    => 'Estimates',
        'purchase'    => 'Purchases',
        'expense'     => 'Expenses',
        'supplier'    => 'Suppliers',
        'staff'       => 'Staff',
        'pay_account' => 'Accounts',
        'work_order'  => 'Work orders',
    );

    public function index()
    {
        $this->require_perm('bookings.view');
        if (!$this->db->table_exists('crm_okm_archive')) {
            $this->render('admin/okm_archive/index', array('title' => 'OkMechanic archive', 'empty' => TRUE));
            return;
        }
        $type = isset(self::TYPES[(string) $this->input->get('t')]) ? (string) $this->input->get('t') : 'estimate';
        $q = trim((string) $this->input->get('q'));
        $per = 50;
        $page = max(1, (int) $this->input->get('p'));

        $counts = array();
        foreach ($this->db->select('entity, COUNT(*) n', FALSE)->group_by('entity')->get('crm_okm_archive')->result_array() as $r) {
            $counts[$r['entity']] = (int) $r['n'];
        }
        $filter = function () use ($type, $q) {
            $this->db->from('crm_okm_archive a')->where('a.entity', $type);
            if ($q !== '') {
                $this->db->group_start()->like('a.number', $q)->or_like('a.title', $q)->or_like('a.status', $q)->group_end();
            }
        };
        $filter();
        $total = $this->db->count_all_results();
        $filter();
        $rows = $this->db->select('a.id, a.number, a.title, a.status, a.amount, a.doc_date, a.work_order_id, w.ro_number', FALSE)
            ->join('crm_work_orders w', 'w.id = a.work_order_id', 'left')
            ->order_by('a.doc_date', 'DESC')->order_by('a.id', 'DESC')
            ->limit($per, ($page - 1) * $per)->get()->result_array();

        $this->render('admin/okm_archive/index', array(
            'title'  => 'OkMechanic archive',
            'types'  => self::TYPES,
            'type'   => $type,
            'q'      => $q,
            'rows'   => $rows,
            'counts' => $counts,
            'pager'  => array('page' => $page, 'pages' => max(1, (int) ceil($total / $per)), 'total' => $total, 'per' => $per, 'param' => 'p',
                'anchor' => 'list', 'path' => 'okmechanic-archive', 'noun' => 'records'),
        ));
    }

    public function view($id)
    {
        $this->require_perm('bookings.view');
        $r = $this->db->table_exists('crm_okm_archive') ? $this->db->where('id', (int) $id)->get('crm_okm_archive')->row_array() : NULL;
        if (!$r) {
            show_404();
        }
        $ro = $r['work_order_id'] ? $this->db->select('id, ro_number')->where('id', (int) $r['work_order_id'])->get('crm_work_orders')->row_array() : NULL;
        $user = $r['user_id'] ? $this->db->select('id, name, mobile')->where('id', (int) $r['user_id'])->get('users')->row_array() : NULL;
        $this->render('admin/okm_archive/view', array(
            'title' => (self::TYPES[$r['entity']] ?? 'Record').' · '.($r['number'] !== '' ? $r['number'] : $r['title']),
            'r'     => $r,
            'data'  => json_decode($r['data'], TRUE),
            'ro'    => $ro,
            'user'  => $user,
            'types' => self::TYPES,
        ));
    }
}
