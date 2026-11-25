<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cloudflare R2 file storage.
 *
 *   php index.php cli/media status                 settings + a write/read/delete test on both buckets
 *   php index.php cli/media migrate preview        what would be copied to R2 (changes nothing)
 *   php index.php cli/media migrate apply          copy existing files to R2 and point the DB at them
 *                                                  (server copies are kept; delete them yourself later)
 *   php index.php cli/media backup <file> [days]   upload a backup from storage/ to the private bucket,
 *                                                  then remove R2 backups older than [days] (default 30)
 */
class Media extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('CLI only', 403);
        }
        $this->load->helper('media');
    }

    public function index()
    {
        $this->status();
    }

    public function status()
    {
        $m = ymo_media();
        if (!$m->enabled()) {
            echo "R2 is OFF: set R2_ACCOUNT_ID, R2_ACCESS_KEY_ID and R2_SECRET_ACCESS_KEY in .env, then recreate the app container.\n";
            echo "Until then uploads stay on the server, exactly as before.\n";
            return;
        }
        $ok = TRUE;
        foreach (array('public', 'private') as $scope) {
            $key = 'healthcheck/'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(3)).'.txt';
            $body = 'ymo '.$scope.' '.gmdate('c');
            $put = $m->put($scope, $key, $body, 'text/plain');
            $get = $put ? $m->get($scope, $key) : NULL;
            $del = $put ? $m->delete($scope, $key) : FALSE;
            $pass = $put && $get === $body && $del;
            $ok = $ok && $pass;
            echo str_pad($scope, 8).($pass ? 'OK  write/read/delete' : 'FAIL '.$m->error)."\n";
            if ($pass && $scope === 'public') {
                $probe = $m->put('public', 'healthcheck/public-url-test.txt', 'ok', 'text/plain');
                echo '        public link: '.$m->public_url('healthcheck/public-url-test.txt')."  (should show \"ok\" in a browser)\n";
            }
        }
        echo $ok ? "R2 is ready.\n" : "Fix the failures above (bucket names, token permissions).\n";
    }

    /** Copy existing server files to R2 and update their DB rows. */
    public function migrate($mode = 'preview')
    {
        $apply = $mode === 'apply';
        if ($apply && !ymo_media_on()) {
            echo "R2 is not configured; nothing to do.\n";
            return;
        }
        $sets = array(
            // table, id column, path column, scope, local prefix => key prefix
            array('site_offers', 'id', 'image_path', 'public', 'uploads/offers/', 'offers/'),
            array('vehicles', 'id', 'image_path', 'public', 'uploads/vehicles/', 'vehicles/'),
            array('crm_candidate_documents', 'id', 'file_path', 'private', 'uploads/crm/resumes/', 'resumes/'),
            array('booking_invoices', 'id', 'pdf_path', 'private', 'uploads/invoices/', 'invoices/'),
            array('crm_wa_messages', 'id', 'media_path', 'private', 'storage/whatsapp/', 'whatsapp/'),
        );
        $total = array('copied' => 0, 'missing' => 0, 'failed' => 0);
        foreach ($sets as $s) {
            list($table, $idc, $col, $scope, $local, $prefix) = $s;
            if (!$this->db->table_exists($table)) {
                continue;
            }
            $rows = $this->db->select($idc.', '.$col)->like($col, $local, 'after')->get($table)->result_array();
            $n = array('copied' => 0, 'missing' => 0, 'failed' => 0);
            foreach ($rows as $r) {
                $path = $r[$col];
                if (!is_file(ymo_media_local_abs($path))) {
                    $n['missing']++;
                    continue;
                }
                if (!$apply) {
                    $n['copied']++;
                    continue;
                }
                $key = $prefix.substr($path, strlen($local));
                $new = ymo_media_offload($path, $scope, $key, NULL, FALSE);
                if ($new !== $path) {
                    $this->db->where($idc, $r[$idc])->update($table, array($col => $new));
                    $n['copied']++;
                } else {
                    $n['failed']++;
                }
            }
            printf("%-26s %-7s %s %d, missing on server %d%s\n", $table, $scope, $apply ? 'copied' : 'to copy', $n['copied'], $n['missing'], $n['failed'] ? ', FAILED '.$n['failed'] : '');
            foreach ($n as $k => $v) {
                $total[$k] += $v;
            }
        }

        // Google post photos kept in the database.
        if ($this->db->table_exists('gbp_posts')) {
            $this->load->model('gbp_post_model');
            $this->gbp_post_model->ensure_schema();
            $rows = $this->db->select('id, image_hash')->where('image IS NOT NULL', NULL, FALSE)->where('image_path IS NULL', NULL, FALSE)->get('gbp_posts')->result_array();
            $c = 0;
            foreach ($rows as $r) {
                if (!$apply) {
                    $c++;
                    continue;
                }
                $img = $this->db->select('image')->get_where('gbp_posts', array('id' => $r['id']))->row_array();
                $stored = ymo_media_put_bytes('public', 'gbp/legacy/'.(int) $r['id'].'-'.$r['image_hash'].'.jpg', $img['image'], 'image/jpeg');
                if ($stored) {
                    $this->db->where('id', $r['id'])->update('gbp_posts', array('image_path' => $stored, 'image' => NULL));
                    $c++;
                } else {
                    $total['failed']++;
                }
            }
            printf("%-26s %-7s %s %d\n", 'gbp_posts (photos in DB)', 'public', $apply ? 'moved' : 'to move', $c);
        }

        if ($apply) {
            echo "\nDone. The server copies are still in public/uploads and storage/whatsapp.\n";
            echo "Check a few offers, invoices and résumés in the CRM, then remove the old folders yourself.\n";
        } else {
            echo "\nPreview only. Run: php index.php cli/media migrate apply\n";
        }
    }

    /** Upload a backup file (path under storage/) and prune old R2 backups. */
    public function backup($file = '', $days = 30)
    {
        if (!ymo_media_on()) {
            echo "R2 not configured: backup kept on the server only.\n";
            return;
        }
        // CLI args can't contain "/", so accept a bare name inside storage/backups/.
        $abs = realpath(rtrim(FCPATH, '/\\').'/../storage/backups/'.basename((string) $file));
        $root = realpath(rtrim(FCPATH, '/\\').'/../storage/backups');
        if (!$abs || !$root || strpos($abs, $root.DIRECTORY_SEPARATOR) !== 0 || !is_file($abs)) {
            echo "Backup file not found in storage/backups: ".$file."\n";
            exit(1);
        }
        $key = 'backups/'.gmdate('Y/m/').basename($abs);
        $stored = ymo_media()->put_file('private', $key, $abs, ymo_media_mime($abs));
        if ($stored === NULL) {
            echo "Upload FAILED: ".ymo_media()->error."\n";
            exit(1);
        }
        echo 'Uploaded '.basename($abs).' ('.round(filesize($abs) / 1048576, 1)." MB) to R2.\n";

        $days = max(7, (int) $days);
        $cut = time() - $days * 86400;
        $list = ymo_media()->list_objects('private', 'backups/', 5000);
        $gone = 0;
        foreach ((array) $list as $o) {
            if (strtotime($o['modified']) < $cut && ymo_media()->delete('private', $o['key'])) {
                $gone++;
            }
        }
        echo 'Removed '.$gone.' R2 backup(s) older than '.$days." days.\n";
    }
}
