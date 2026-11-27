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
<?php
$row = ! empty($attributes['row']);
// In a row the email comes first, then the phone, then the address (the order of a contact line: write, call, visit)
$order = $row ? ['emails', 'phones', 'places'] : ['phones', 'emails', 'places'];
$groups = ['phones' => $phones, 'emails' => $emails, 'places' => $places];
$components = [
    'phones' => '<x-site-phone :index="$i" :label="$label" />',
    'emails' => '<x-site-email :index="$i" :label="$label" />',
    'places' => '<x-site-address :index="$i" :label="$label" />',
];
?>
<div <?php echo get_block_wrapper_attributes(['class' => 'pkde-block pkde-site-contact' . ($row ? ' pkde-site-contact--row' : '')]); ?>>
    <ul class="pkde-site-contact__list">
        <?php foreach ($order as $group) : ?>
            <?php foreach ($groups[$group] as $i => $unused) : ?>
                <li><?php echo \Illuminate\Support\Facades\Blade::render($components[$group], ['i' => $i, 'label' => count($groups[$group]) > 1]); ?></li>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </ul>
</div>
