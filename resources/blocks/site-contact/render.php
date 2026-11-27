<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Contact details block: the phone numbers, email addresses and addresses of Site details, in the section's colours. Nothing to type.
 *
 * @var array $attributes
 */
$phones = ($attributes['showPhones'] ?? true) ? \App\site_phones() : [];
$emails = ($attributes['showEmails'] ?? true) ? \App\site_emails() : [];
$places = ($attributes['showAddresses'] ?? true) ? \App\site_locations() : [];

if ($phones === [] && $emails === [] && $places === []) {
    return;
}
?>
<div <?php echo get_block_wrapper_attributes(['class' => 'pkde-block pkde-site-contact']); ?>>
    <ul class="pkde-site-contact__list">
        <?php foreach ($phones as $i => $phone) : ?>
            <li><?php echo \Illuminate\Support\Facades\Blade::render('<x-site-phone :index="$i" :label="$label" />', ['i' => $i, 'label' => count($phones) > 1]); ?></li>
        <?php endforeach; ?>
        <?php foreach ($emails as $i => $email) : ?>
            <li><?php echo \Illuminate\Support\Facades\Blade::render('<x-site-email :index="$i" :label="$label" />', ['i' => $i, 'label' => count($emails) > 1]); ?></li>
        <?php endforeach; ?>
        <?php foreach ($places as $i => $place) : ?>
            <li><?php echo \Illuminate\Support\Facades\Blade::render('<x-site-address :index="$i" :label="$label" />', ['i' => $i, 'label' => count($places) > 1]); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
