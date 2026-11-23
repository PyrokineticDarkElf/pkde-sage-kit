<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Opening hours block (WP-106): the weekly hours of Site details (consecutive days with the same hours are one row) and the holidays still to come. Nothing to type.
 *
 * @var array $attributes
 */
if (! \App\site_has_hours((array) \App\site_detail('hours', []))) {
    return;
}
?>
<div <?php echo get_block_wrapper_attributes(['class' => 'pkde-block pkde-site-hours-block']); ?>>
    <?php echo \Illuminate\Support\Facades\Blade::render('<x-site-hours :exceptions="$exceptions" />', ['exceptions' => (bool) ($attributes['showExceptions'] ?? true)]); ?>
</div>
