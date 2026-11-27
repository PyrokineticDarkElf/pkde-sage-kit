<?php

if (! defined('ABSPATH')) {
    exit; // never run when requested directly
}

/** Social links block: the social profiles of Site details as icon links, in the section's colours. Nothing to type. */
if (\App\social_links() === []) {
    return;
}
?>
<div <?php echo get_block_wrapper_attributes(['class' => 'pkde-block pkde-site-socials']); ?>>
    <?php echo view('pkde-kit::partials.social-links', ['class' => 'flex flex-wrap gap-3'])->render(); ?>
</div>
