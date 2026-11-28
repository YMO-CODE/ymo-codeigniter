<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Job card photos: check, shrink and store in Cloud Storage; stream back to staff.
 * Every photo is re-encoded as JPEG (max 1600 px, location / camera data removed)
 * with a 360 px thumbnail. Nothing is kept on the CRM server's disk.
 */
class Job_photo_service
{
    const MAX_BYTES = 12582912;   // 12 MB per original
    const MAX_EDGE = 1600;
    const THUMB_EDGE = 360;

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('crm_work_order_model');
        $this->CI->load->library('gcs_client');
    }

    /** $_FILES[field] (single or multiple) as a list of {tmp_name, size, error}. */
    public function files_from_upload($field)
    {
        if (empty($_FILES[$field])) {
            return array();
        }
        $f = $_FILES[$field];
        $out = array();
        if (is_array($f['tmp_name'])) {
            foreach ($f['tmp_name'] as $i => $tmp) {
                if ((int) $f['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $out[] = array('tmp_name' => $tmp, 'size' => (int) $f['size'][$i], 'error' => (int) $f['error'][$i]);
            }
        } elseif ((int) $f['error'] !== UPLOAD_ERR_NO_FILE) {
            $out[] = array('tmp_name' => $f['tmp_name'], 'size' => (int) $f['size'], 'error' => (int) $f['error']);
        }
        return $out;
    }

    /**
     * @return array [photo id or 0, error message]
     */
    public function save($work_order_id, array $file, $kind, $admin_id, $tech_id, $client_uid)
    {
        if ($client_uid !== NULL && $client_uid !== '') {
            $dup = $this->CI->crm_work_order_model->find_photo_by_uid($client_uid);
            if ($dup) {
                return array((int) $dup['id'], '');
            }
        }
        if ((int) $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) && !is_file($file['tmp_name'])) {
            return array(0, 'The photo did not upload completely.');
        }
        if ((int) $file['size'] > self::MAX_BYTES) {
            return array(0, 'Photo is larger than 12 MB.');
        }
        $info = @getimagesize($file['tmp_name']);
        $types = array(IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp');
        if (!$info || !isset($types[$info[2]]) || !function_exists($types[$info[2]])) {
            return array(0, 'Only JPEG, PNG or WebP photos.');
        }
        if ($info[0] * $info[1] > 50000000) {
            return array(0, 'Photo resolution is too large.');
        }
        $img = @call_user_func($types[$info[2]], $file['tmp_name']);
        if (!$img) {
            return array(0, 'Could not read that photo.');
        }
        if ($info[2] === IMAGETYPE_JPEG) {
            $img = $this->_apply_orientation($img, $this->_jpeg_orientation($file['tmp_name']));
        }
        list($full, $w, $h) = $this->_jpeg($img, self::MAX_EDGE, 78);
        list($thumb) = $this->_jpeg($img, self::THUMB_EDGE, 70);
        imagedestroy($img);

        $wo = $this->CI->crm_work_order_model->find($work_order_id);
        $ro = $wo ? preg_replace('/[^A-Za-z0-9]/', '', $wo['ro_number']) : 'wo'.$work_order_id;
        $base = 'jobs/'.$ro.'/'.date('Ymd-His').'-'.bin2hex(random_bytes(4));
        if (!$this->CI->gcs_client->put($base.'.jpg', $full) || !$this->CI->gcs_client->put($base.'_t.jpg', $thumb)) {
            return array(0, $this->CI->gcs_client->last_error() ?: 'Could not save the photo to storage.');
        }
        $kind = isset(Crm_work_order_model::PHOTO_KINDS[$kind]) ? $kind : 'during';
        $id = $this->CI->crm_work_order_model->add_photo(array(
            'work_order_id' => (int) $work_order_id,
            'kind'          => $kind,
            'object_name'   => $base.'.jpg',
            'thumb_name'    => $base.'_t.jpg',
            'bytes'         => strlen($full),
            'width'         => $w,
            'height'        => $h,
            'admin_id'      => (int) $admin_id ?: NULL,
            'technician_id' => (int) $tech_id ?: NULL,
            'client_uid'    => $client_uid !== NULL && $client_uid !== '' ? mb_substr((string) $client_uid, 0, 40) : NULL,
        ));
        if (!$id && $client_uid) {
            $dup = $this->CI->crm_work_order_model->find_photo_by_uid($client_uid);   // parallel retry
            return array($dup ? (int) $dup['id'] : 0, $dup ? '' : 'Could not save the photo.');
        }
        return array($id, $id ? '' : 'Could not save the photo.');
    }

    /** Sends the photo bytes to the browser (staff are already checked by the caller). */
    public function stream(array $photo, $thumb = FALSE)
    {
        $name = $thumb && $photo['thumb_name'] !== '' ? $photo['thumb_name'] : $photo['object_name'];
        $bytes = $this->CI->gcs_client->get($name);
        if ($bytes === NULL) {
            set_status_header(404);
            exit;
        }
        $this->CI->output
            ->set_header('Content-Type: image/jpeg')
            ->set_header('Cache-Control: private, max-age=604800, immutable')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_output($bytes);
    }

    public function remove(array $photo)
    {
        $this->CI->crm_work_order_model->delete_photo($photo['id']);
        $this->CI->gcs_client->delete($photo['object_name']);
        if ($photo['thumb_name'] !== '') {
            $this->CI->gcs_client->delete($photo['thumb_name']);
        }
    }

    // --- image helpers ---------------------------------------------------------

    /** @return array [jpeg bytes, width, height] */
    protected function _jpeg($img, $max_edge, $quality)
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min(1, $max_edge / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $out = imagecreatetruecolor($nw, $nh);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));   // PNG transparency → white
        imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        ob_start();
        imagejpeg($out, NULL, $quality);
        $bytes = ob_get_clean();
        imagedestroy($out);
        return array($bytes, $nw, $nh);
    }

    protected function _apply_orientation($img, $o)
    {
        switch ($o) {
            case 3: return imagerotate($img, 180, 0);
            case 6: return imagerotate($img, -90, 0);
            case 8: return imagerotate($img, 90, 0);
            case 2: imageflip($img, IMG_FLIP_HORIZONTAL); return $img;
            case 4: imageflip($img, IMG_FLIP_VERTICAL); return $img;
            case 5: imageflip($img, IMG_FLIP_HORIZONTAL); return imagerotate($img, 90, 0);
            case 7: imageflip($img, IMG_FLIP_HORIZONTAL); return imagerotate($img, -90, 0);
        }
        return $img;
    }

    /** EXIF orientation (1–8) of a JPEG, read without the exif extension. */
    protected function _jpeg_orientation($path)
    {
        $f = @fopen($path, 'rb');
        if (!$f) {
            return 1;
        }
        $data = fread($f, 131072);
        fclose($f);
        if (substr($data, 0, 2) !== "\xFF\xD8") {
            return 1;
        }
        $pos = 2;
        $len = strlen($data);
        while ($pos + 4 < $len) {
            if (ord($data[$pos]) !== 0xFF) {
                return 1;
            }
            $marker = ord($data[$pos + 1]);
            $seg = unpack('n', substr($data, $pos + 2, 2))[1];
            if ($marker === 0xE1 && substr($data, $pos + 4, 6) === "Exif\0\0") {
                $tiff = $pos + 10;
                $le = substr($data, $tiff, 2) === 'II';
                $u16 = function ($o) use ($data, $le) { $v = unpack($le ? 'v' : 'n', substr($data, $o, 2)); return $v ? $v[1] : 0; };
                $u32 = function ($o) use ($data, $le) { $v = unpack($le ? 'V' : 'N', substr($data, $o, 4)); return $v ? $v[1] : 0; };
                $ifd = $tiff + $u32($tiff + 4);
                $n = $u16($ifd);
                for ($i = 0; $i < $n && $ifd + 2 + ($i + 1) * 12 <= $len; $i++) {
                    $e = $ifd + 2 + $i * 12;
                    if ($u16($e) === 0x0112) {
                        $o = $u16($e + 8);
                        return ($o >= 1 && $o <= 8) ? $o : 1;
                    }
                }
                return 1;
            }
            if ($marker === 0xDA) {
                return 1;
            }
            $pos += 2 + $seg;
        }
        return 1;
    }
}
