<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Marketing visual theme (v1 default). v2 = editorial layout/CSS only; content unchanged.
 *
 * Enable globally: YMO_MARKETING_THEME=v2 in env / Docker.
 * Or list paths below (homepage = '').
 */
return array(
    /** TRUE = all marketing pages use v2 views/CSS (content unchanged). Override with theme v1 on a page if needed. */
    'v2_all'   => TRUE,
    'v2_paths' => array(),
    'v2_prefixes' => array(),
    /**
     * Card chrome for v2: default | instrument (workshop display / phosphor panels).
     * Override: YMO_MARKETING_CARD_SKIN=default|instrument
     */
    'v2_card_skin' => 'default',
);
