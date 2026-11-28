<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Move OkMechanic data into the CRM. Sends nothing to customers.
 *
 *   php index.php cli/okm_import preview [name]   dry run: shows what would be added / matched (saves nothing)
 *   php index.php cli/okm_import apply   [name]   does the import (safe to run again: skips what is already in)
 *
 * [name] is a file in storage/import/okm/ (default okm-export.json.gz): one JSON (optionally gzipped) holding
 * {"main": <okm-export-main.json>, "workorder_details": <okm-export-workorder-details.json>}.
 */
class Okm_import extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('CLI only', 403);
        }
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $this->load->database();
    }

    public function index()
    {
        $this->preview();
    }

    public function preview($file = '')
    {
        $this->_go($file, FALSE);
    }

    public function apply($file = '')
    {
        $this->_go($file, TRUE);
    }

    protected function _go($file, $apply)
    {
        $name = $file !== '' ? basename($file) : 'okm-export.json.gz';
        $path = realpath(FCPATH.'../storage') .'/import/okm/'.$name;
        if (!is_file($path)) {
            echo "File not found: {$path}\n";
            exit(1);
        }
        $raw = file_get_contents($path);
        if (substr($raw, 0, 2) === "\x1f\x8b") {
            $raw = gzdecode($raw);
        }
        $data = json_decode($raw, TRUE);
        unset($raw);
        if (!is_array($data) || !isset($data['main'], $data['workorder_details'])) {
            echo "That file is not an OkMechanic export (needs \"main\" and \"workorder_details\").\n";
            exit(1);
        }

        // Anything that could message imported customers on its own?
        if ($this->db->table_exists('crm_campaigns')) {
            $live = $this->db->where_in('status', array('scheduled', 'sending'))->count_all_results('crm_campaigns');
            if ($live) {
                echo "STOP: {$live} campaign(s) are scheduled or sending. Their recipient lists are built when they go out,\n";
                echo "so newly imported customers could receive them. Pause or finish those campaigns first.\n";
                exit(1);
            }
        }

        echo ($apply ? 'IMPORTING' : 'PREVIEW (dry run)').' from '.$path."\n";
        echo 'Export taken: '.($data['main']['exported_at'] ?? '?')."\n";
        $t = microtime(TRUE);
        $this->load->library('okm_importer');
        list($stats, $notes) = $this->okm_importer->run($data['main'], $data['workorder_details'], $apply);

        ksort($stats);
        echo "\n-- Counts --\n";
        foreach ($stats as $k => $v) {
            printf("  %-40s %s\n", str_replace('_', ' ', $k), number_format($v));
        }
        echo "\n-- Notes --\n";
        foreach ($notes as $n) {
            echo '  '.$n."\n";
        }
        printf("\nTook %.1fs\n", microtime(TRUE) - $t);
    }
}
