<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container py-5 text-center">
    <p class="display-1 mb-2 text-danger fw-bold">404</p>
    <h1 class="h3 mb-2">We couldn't find that page.</h1>
    <p class="ymo-muted mb-4">It may have moved, or the link is no longer valid.</p>
    <a href="<?= site_url('/'); ?>" class="btn btn-primary">Back to home</a>
    <a href="<?= site_url('packages'); ?>" class="btn btn-outline-primary">Browse packages</a>
</div>
