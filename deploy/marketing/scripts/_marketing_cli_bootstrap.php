<?php
/**
 * Bootstrap for marketing SEO CLI scripts (run on VPS host or in Docker).
 * Loads helpers without full CodeIgniter front controller.
 */

if (!function_exists('marketing_cli_repo_root')) {
    function marketing_cli_repo_root()
    {
        $root = realpath(__DIR__.'/../../../');
        if ($root !== false && is_dir($root.DIRECTORY_SEPARATOR.'application')) {
            return $root;
        }
        // Docker: /var/www/deploy/marketing/scripts → application lives at /var/www/application
        $deploy_root = realpath(__DIR__.'/../../..');
        if ($deploy_root !== false) {
            $www = dirname($deploy_root);
            if (is_dir($www.DIRECTORY_SEPARATOR.'application')) {
                return $www;
            }
        }
        fwrite(STDERR, "Cannot locate application/ — run from repo root or mount deploy + application in Docker.\n");
        exit(1);
    }
}

$repo_root = marketing_cli_repo_root();

if (!defined('BASEPATH')) {
    define('BASEPATH', $repo_root.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR);
}
if (!defined('APPPATH')) {
    define('APPPATH', $repo_root.DIRECTORY_SEPARATOR.'application'.DIRECTORY_SEPARATOR);
}
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', getenv('CI_ENV') ?: 'production');
}

if (!function_exists('config_item')) {
    function config_item($item)
    {
        static $map = array(
            'charset' => 'UTF-8',
        );
        return isset($map[$item]) ? $map[$item] : false;
    }
}

if (!function_exists('html_escape')) {
    function html_escape($var, $double_encode = true)
    {
        if (empty($var)) {
            return $var;
        }
        if (is_array($var)) {
            foreach (array_keys($var) as $key) {
                $var[$key] = html_escape($var[$key], $double_encode);
            }
            return $var;
        }
        return htmlspecialchars((string) $var, ENT_QUOTES, 'UTF-8', $double_encode);
    }
}

if (!function_exists('marketing_cli_load_helpers')) {
    function marketing_cli_load_helpers()
    {
        foreach (array(
            'marketing_helper.php',
            'marketing_seo_growth_helper.php',
            'marketing_seo_enhancements_helper.php',
            'marketing_seo_project_helper.php',
            'marketing_internal_links_helper.php',
            'marketing_similarity_helper.php',
        ) as $file) {
            $path = APPPATH.'helpers/'.$file;
            if (is_file($path)) {
                require_once $path;
            }
        }
    }
}

marketing_cli_load_helpers();

if (!function_exists('marketing_cli_pages_data')) {
    /** @return array<string,array> */
    function marketing_cli_pages_data()
    {
        if (function_exists('marketing_pages_data')) {
            return marketing_pages_data();
        }
        $pages = require APPPATH.'config/marketing_pages_data.php';
        if (function_exists('marketing_enrich_pages')) {
            $pages = marketing_enrich_pages($pages);
        }
        return is_array($pages) ? $pages : array();
    }
}
