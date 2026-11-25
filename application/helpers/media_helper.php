<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Uploaded files: Cloudflare R2 when configured, the server's disk otherwise.
 * Stored paths: r2/<key> (public bucket), r2p/<key> (private), or local
 * (uploads/… under public/, storage/… beside it). See libraries/Media_store.php.
 */

if (!class_exists('Media_store', FALSE)) {
    require_once APPPATH.'libraries/Media_store.php';
}

if (!function_exists('ymo_media')) {
    /** @return Media_store */
    function ymo_media()
    {
        $CI = &get_instance();
        if (!isset($CI->media_store)) {
            $CI->load->library('media_store');
        }
        return $CI->media_store;
    }
}

if (!function_exists('ymo_media_on')) {
    function ymo_media_on()
    {
        return ymo_media()->enabled();
    }
}

if (!function_exists('ymo_media_local_abs')) {
    /** Absolute local path for a local stored path (no check that it exists). */
    function ymo_media_local_abs($path)
    {
        $path = ltrim(str_replace('\\', '/', (string) $path), '/');
        if (strpos($path, 'storage/') === 0) {
            return rtrim(FCPATH, '/\\').'/../'.$path;
        }
        return rtrim(FCPATH, '/\\').'/'.$path;
    }
}

if (!function_exists('ymo_media_offload')) {
    /**
     * Move a file the app just wrote locally into R2 and return the new stored path.
     * Without R2 (or if the upload fails) the local path is returned unchanged,
     * so the feature keeps working either way.
     *
     * @param string $local_rel stored local path, e.g. uploads/offers/x.jpg
     * @param string $scope     public | private
     * @param string $key       object key, e.g. offers/x.jpg
     */
    function ymo_media_offload($local_rel, $scope, $key, $content_type = NULL, $remove_local = TRUE)
    {
        if (!ymo_media_on() || $local_rel === NULL || $local_rel === '') {
            return $local_rel;
        }
        list($already) = Media_store::parse($local_rel);
        if ($already !== NULL) {
            return $local_rel;
        }
        $abs = ymo_media_local_abs($local_rel);
        if (!is_file($abs)) {
            return $local_rel;
        }
        $type = $content_type ?: ymo_media_mime($abs);
        $extra = $scope === 'public' ? array('cache-control' => 'public, max-age=31536000, immutable') : array();
        $stored = ymo_media()->put_file($scope, $key, $abs, $type, $extra);
        if ($stored === NULL) {
            log_message('error', '[media] offload kept local copy of '.$local_rel.': '.ymo_media()->error);
            return $local_rel;
        }
        if ($remove_local) {
            @unlink($abs);
        }
        return $stored;
    }
}

if (!function_exists('ymo_media_put_bytes')) {
    /** Store bytes straight in R2. @return string|null stored path */
    function ymo_media_put_bytes($scope, $key, $bytes, $content_type)
    {
        if (!ymo_media_on()) {
            return NULL;
        }
        $extra = $scope === 'public' ? array('cache-control' => 'public, max-age=31536000, immutable') : array();
        return ymo_media()->put($scope, $key, $bytes, $content_type, $extra);
    }
}

if (!function_exists('ymo_media_url')) {
    /** Browser URL for a public file (R2 or local). Private paths return NULL. */
    function ymo_media_url($path)
    {
        if ($path === NULL || $path === '') {
            return NULL;
        }
        list($scope, $key) = Media_store::parse($path);
        if ($scope === 'public') {
            return ymo_media()->public_url($key);
        }
        if ($scope === 'private') {
            return NULL;
        }
        return base_url(ltrim($key, '/'));
    }
}

if (!function_exists('ymo_media_exists')) {
    function ymo_media_exists($path)
    {
        if ($path === NULL || $path === '') {
            return FALSE;
        }
        list($scope, $key) = Media_store::parse($path);
        if ($scope === NULL) {
            return is_file(ymo_media_local_abs($key));
        }
        return ymo_media_on() && ymo_media()->exists($scope, $key);
    }
}

if (!function_exists('ymo_media_bytes')) {
    /** @return string|null file contents */
    function ymo_media_bytes($path)
    {
        if ($path === NULL || $path === '') {
            return NULL;
        }
        list($scope, $key) = Media_store::parse($path);
        if ($scope === NULL) {
            $abs = ymo_media_local_abs($key);
            return is_file($abs) ? (string) file_get_contents($abs) : NULL;
        }
        return ymo_media_on() ? ymo_media()->get($scope, $key) : NULL;
    }
}

if (!function_exists('ymo_media_local_copy')) {
    /**
     * A local file path for code that needs one (email attachments, WhatsApp
     * media upload). R2 files are downloaded to a temp file removed at shutdown.
     * @return string absolute path, or '' if the file is missing
     */
    function ymo_media_local_copy($path, $ext = NULL)
    {
        if ($path === NULL || $path === '') {
            return '';
        }
        list($scope, $key) = Media_store::parse($path);
        if ($scope === NULL) {
            $abs = ymo_media_local_abs($key);
            return is_file($abs) ? $abs : '';
        }
        $bytes = ymo_media_bytes($path);
        if ($bytes === NULL) {
            return '';
        }
        $dir = rtrim(sys_get_temp_dir(), '/\\').'/ymo-media-'.bin2hex(random_bytes(6));
        @mkdir($dir, 0700, TRUE);
        $tmp = $dir.'/'.basename($ext !== NULL ? pathinfo($key, PATHINFO_FILENAME).'.'.$ext : $key);
        file_put_contents($tmp, $bytes);
        register_shutdown_function(function () use ($tmp, $dir) {
            @unlink($tmp);
            @rmdir($dir);
        });
        return $tmp;
    }
}

if (!function_exists('ymo_media_delete')) {
    function ymo_media_delete($path)
    {
        if ($path === NULL || $path === '') {
            return;
        }
        list($scope, $key) = Media_store::parse($path);
        if ($scope === NULL) {
            $abs = ymo_media_local_abs($key);
            if (is_file($abs)) {
                @unlink($abs);
            }
            return;
        }
        if (ymo_media_on()) {
            ymo_media()->delete($scope, $key);
        }
    }
}

if (!function_exists('ymo_media_send')) {
    /**
     * Stream a stored file to the browser (after the caller has checked access).
     * Ends the request; 404 if the file is missing.
     */
    function ymo_media_send($path, $content_type, $filename, $disposition = 'inline', $cache = 'private, max-age=0, no-store')
    {
        $bytes = ymo_media_bytes($path);
        if ($bytes === NULL) {
            show_error('File not found.', 404);
        }
        header('Content-Type: '.$content_type);
        header('Content-Disposition: '.$disposition.'; filename="'.str_replace(array('"', "\r", "\n"), '', $filename).'"');
        header('Content-Length: '.strlen($bytes));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: '.$cache);
        echo $bytes;
        exit;
    }
}

if (!function_exists('ymo_media_mime')) {
    function ymo_media_mime($file)
    {
        $map = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
            'gif' => 'image/gif', 'pdf' => 'application/pdf', 'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'gz' => 'application/gzip', 'json' => 'application/json', 'csv' => 'text/csv');
        $ext = strtolower(pathinfo((string) $file, PATHINFO_EXTENSION));
        return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
    }
}
