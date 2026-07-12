<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php $this->load->view('bookings/_stepper', array('step' => $step)); ?>

            <div class="md-card-filled mb-3">
                <h5 class="mb-1"><span class="mi mi-leading">build</span>Booking <?= html_escape($package['name']); ?></h5>
                <p class="ymo-muted small mb-0">&#8377; <?= number_format((float) $package['price']); ?></p>
            </div>

            <h2 class="h4 mb-3"><span class="mi mi-leading">directions_car</span>Which car is this for?</h2>

            <?php if (empty($vehicles)): ?>
                <div class="md-card-elevated text-center py-5">
                    <span class="mi mi-xl" style="color:var(--ymo-grey-500);">no_crash</span>
                    <h5 class="mb-2 mt-2">You haven't added a vehicle yet.</h5>
                    <p class="ymo-muted">Add your car details once and we'll save them for next time.</p>
                    <a href="<?= site_url('vehicles/new?next='.urlencode('booking/vehicle')); ?>" class="btn btn-primary">
                        <span class="mi mi-leading">add</span>Add vehicle
                    </a>
                </div>
            <?php else:
                $active_bookings = isset($active_bookings) ? $active_bookings : array();
                $all_blocked = count($vehicles) > 0;
                foreach ($vehicles as $v) {
                    if (empty($active_bookings[(int) $v['id']])) {
                        $all_blocked = FALSE;
                        break;
                    }
                }
            ?>
                <?php if ($all_blocked): ?>
                    <div class="alert alert-warning">
                        All your saved vehicles currently have an active service booking.
                        Check <a href="<?= site_url('account/bookings'); ?>">My bookings</a> or call us for help.
                    </div>
                <?php endif; ?>
                <?= form_open(site_url('booking/vehicle')); ?>
                    <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>
                    <div class="row g-3">
                        <?php foreach ($vehicles as $v):
                            $vid = (int) $v['id'];
                            $active = $active_bookings[$vid] ?? NULL;
                            $blocked = !empty($active);
                        ?>
                            <div class="col-md-6">
                                <label class="md-card-outlined d-flex gap-3 align-items-start<?= $blocked ? ' opacity-75' : ''; ?>"
                                       style="cursor:<?= $blocked ? 'not-allowed' : 'pointer'; ?>;">
                                    <input type="radio" name="vehicle_id" value="<?= $vid; ?>"<?= $blocked ? '' : ' required'; ?>
                                        <?= $blocked ? 'disabled' : ''; ?>
                                        <?= (!$blocked && !empty($draft['vehicle_id']) && (int) $draft['vehicle_id'] === $vid) ? 'checked' : ''; ?>>
                                    <div>
                                        <strong><?= html_escape($v['make_name']); ?> <?= html_escape($v['variant']); ?></strong><br>
                                        <span class="ymo-muted font-monospace small"><?= html_escape($v['vehicle_number']); ?></span>
                                        <?php if ($blocked): ?>
                                            <br><span class="badge bg-warning-subtle text-dark mt-1">
                                                In service — #<?= html_escape($active['reference']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="<?= site_url('vehicles/new?next='.urlencode('booking/vehicle')); ?>" class="btn btn-outline-primary btn-sm">
                            <span class="mi mi-sm mi-leading">add</span>Add another
                        </a>
                        <button class="btn btn-primary" type="submit"<?= $all_blocked ? ' disabled' : ''; ?>>
                            Continue<span class="mi mi-trailing">arrow_forward</span>
                        </button>
                    </div>
                <?= form_close(); ?>
            <?php endif; ?>
        </div>
    </div>
</div>
