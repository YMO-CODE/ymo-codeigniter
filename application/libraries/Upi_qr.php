<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * UPI payment links and QR codes for invoices: "upi://pay" with YMO's UPI ID, the exact
 * amount and the invoice number, so any UPI app (GPay, PhonePe, Paytm, bank apps) opens
 * with everything filled in. QR drawing: application/third_party/qrcode (MIT).
 */
class Upi_qr
{
    public function __construct()
    {
        require_once APPPATH.'third_party/qrcode/qrcode.php';
    }

    public static function valid_vpa($vpa)
    {
        return (bool) preg_match('/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/', trim((string) $vpa));
    }

    /** upi://pay link (NPCI UPI linking spec). */
    public static function link($vpa, $payee_name, $amount, $note, $ref = '')
    {
        $q = array(
            'pa' => trim($vpa),
            'pn' => mb_substr(trim($payee_name), 0, 50),
            'am' => number_format((float) $amount, 2, '.', ''),
            'cu' => 'INR',
            'tn' => mb_substr(trim($note), 0, 50),
        );
        if ($ref !== '') {
            $q['tr'] = mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $ref), 0, 35);
        }
        // The UPI ID goes in as-is (validated: letters, digits, . - _ @); some apps reject "%40".
        $out = 'pa='.$q['pa'];
        unset($q['pa']);
        return 'upi://pay?'.$out.'&'.http_build_query($q, '', '&', PHP_QUERY_RFC3986);
    }

    protected function _matrix($text)
    {
        $qr = QRCode::getMinimumQRCode($text, QR_ERROR_CORRECT_LEVEL_M);
        $n = $qr->getModuleCount();
        $m = array();
        for ($r = 0; $r < $n; $r++) {
            for ($c = 0; $c < $n; $c++) {
                $m[$r][$c] = $qr->isDark($r, $c);
            }
        }
        return $m;
    }

    /** Inline SVG (for web pages). */
    public function svg($text, $px = 240)
    {
        $m = $this->_matrix($text);
        $n = count($m);
        $q = 4; // quiet zone
        $d = '';
        foreach ($m as $r => $row) {
            foreach ($row as $c => $dark) {
                if ($dark) {
                    $d .= 'M'.($c + $q).','.($r + $q).'h1v1h-1z';
                }
            }
        }
        $size = $n + 2 * $q;
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$size.' '.$size.'" width="'.(int) $px.'" height="'.(int) $px.'" shape-rendering="crispEdges" role="img" aria-label="UPI QR code">'
            .'<rect width="100%" height="100%" fill="#fff"/><path fill="#000" d="'.$d.'"/></svg>';
    }

    /** PNG bytes (for PDFs). */
    public function png($text, $scale = 6)
    {
        $m = $this->_matrix($text);
        $n = count($m);
        $q = 4;
        $size = ($n + 2 * $q) * $scale;
        $im = imagecreate($size, $size);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefill($im, 0, 0, $white);
        foreach ($m as $r => $row) {
            foreach ($row as $c => $dark) {
                if ($dark) {
                    $x = ($c + $q) * $scale;
                    $y = ($r + $q) * $scale;
                    imagefilledrectangle($im, $x, $y, $x + $scale - 1, $y + $scale - 1, $black);
                }
            }
        }
        ob_start();
        imagepng($im);
        imagedestroy($im);
        return ob_get_clean();
    }
}
