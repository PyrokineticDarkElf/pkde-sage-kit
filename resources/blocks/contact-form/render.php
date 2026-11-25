<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/**
 * Contact form block (WP-108 B): the Contact Form 7 form tagged with the lookup key (App\form_id(), `contact` by default), in the section's colours. The form itself is the
 * site's (bin/setup tags it; bin/deploy/cf7-form-key.php tags an existing site's). Without one, an editor sees where to set it up and a visitor sees nothing.
 *
 * @var array $attributes
 */
$key = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($attributes['formKey'] ?? 'contact'))) ?: 'contact';
$shortcode = \App\form_shortcode($key);

if ($shortcode === '') {
    if (function_exists('current_user_can') && current_user_can('edit_posts')) {
        echo '<div ' . get_block_wrapper_attributes(['class' => 'pkde-block pkde-contact-form pkde-contact-form--missing']) . '><p>' . esc_html(sprintf(__('The contact form goes here: no Contact Form 7 form has the key "%s" yet. A visitor sees nothing.', 'sage'), $key)) . '</p></div>';
    }

    return;
}
?>
<div <?php echo get_block_wrapper_attributes(['class' => 'pkde-block pkde-contact-form']); ?>>
    <?php echo do_shortcode($shortcode); ?>
</div>
