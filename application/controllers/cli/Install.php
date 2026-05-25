<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * One-shot installer commands. CLI only.
 *
 *   php public/index.php cli/install create_admin <email> [name]
 *   php public/index.php cli/install set_admin_password <email> [plaintext]
 *   php public/index.php cli/install hash_password <plaintext>
 *
 * If <plaintext> is omitted from set_admin_password, a random 16-char
 * password is generated and printed.
 */
class Install extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            show_error('CLI only', 403);
        }
        $this->load->model('admin_model');
    }

    public function create_admin($email = '', $name = 'YMO Admin')
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->_die("Usage: cli/install create_admin <email> [name]\n");
        }
        if ($this->admin_model->find_by_email($email)) {
            $this->_die("An admin with that email already exists.\n");
        }
        $password = $this->_random_password(16);
        $this->admin_model->create(array(
            'name'        => $name,
            'email'       => $email,
            'password'    => $password,
            'role'        => 'admin',
            'crm_role_id' => 1,
        ));
        fwrite(STDOUT, "Admin created.\n");
        fwrite(STDOUT, "  Email:    $email\n");
        fwrite(STDOUT, "  Password: $password\n");
        fwrite(STDOUT, "Please rotate this password after the first sign-in.\n");
    }

    public function set_admin_password($email = '', $plain = '')
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->_die("Usage: cli/install set_admin_password <email> [plaintext]\n");
        }
        $row = $this->admin_model->find_by_email($email);
        if (!$row) {
            $this->_die("No admin with that email.\n");
        }

        if ($plain !== '') {
            $min = (int) $this->config->item('auth_password_min');
            if ($min > 0 && strlen($plain) < $min) {
                $this->_die("Password must be at least $min characters.\n");
            }
            $password = $plain;
            $generated = FALSE;
        } else {
            $password = $this->_random_password(16);
            $generated = TRUE;
        }

        $this->admin_model->update_password($row['id'], $password);

        if ($generated) {
            fwrite(STDOUT, "New password for $email: $password\n");
            fwrite(STDOUT, "Please rotate this password after the next sign-in.\n");
        } else {
            fwrite(STDOUT, "Password updated for $email.\n");
        }
    }

    public function hash_password($plain = '')
    {
        if ($plain === '') { $this->_die("Usage: cli/install hash_password <plaintext>\n"); }
        fwrite(STDOUT, password_hash($plain, PASSWORD_BCRYPT)."\n");
    }

    protected function _random_password($length = 16)
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#%^*';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }

    protected function _die($msg)
    {
        fwrite(STDERR, $msg);
        exit(1);
    }
}
