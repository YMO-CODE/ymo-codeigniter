<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tiny wrapper around the `audit_log` table. Use it for admin-side mutations
 * (status changes, package edits, password rotations) so we have a paper
 * trail without bolting an event-bus onto the codebase.
 *
 *   $this->audit->log('admin', $admin_id, 'booking.status_change', 'booking', $booking_id, ['from'=>$old, 'to'=>$new]);
 */
class Audit
{
    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    public function log($actor_type, $actor_id, $action, $entity = NULL, $entity_id = NULL, array $meta = array())
    {
        $this->CI->db->insert('audit_log', array(
            'actor_type' => $actor_type,
            'actor_id'   => $actor_id ? (int) $actor_id : NULL,
            'action'     => $action,
            'entity'     => $entity,
            'entity_id'  => $entity_id ? (int) $entity_id : NULL,
            'meta_json'  => $meta ? json_encode($meta) : NULL,
            'ip_address' => $this->CI->input->ip_address(),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }
}
