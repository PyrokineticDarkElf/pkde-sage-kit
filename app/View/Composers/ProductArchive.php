<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class ProductArchive extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'archive-product',
    ];

    /**
     * Data passed to the view: plain values, so the view can test them (a public method would arrive as an always-true object).
     */
    protected function with(): array
    {
        return ['shopPageContent' => $this->shopPageContent()];
    }

    /**
     * The plain shop (not a search or a Sale/New view) is the Shop page's own content, rendered like any page; its Shop products
     * block prints the product archive where it sits. Without that block the archive follows the content, so the shop always lists
     * products. Null on every other product archive, which uses the standard archive layout.
     */
    protected function shopPageContent(): ?string
    {
        $page = is_shop() && ! is_search() && ! isset($_GET['sage_view']) ? get_post(wc_get_page_id('shop')) : null;

        // Only what a visitor may read: published (or readable by this user, for a preview) and not behind a password (MGR-67, tcs).
        if (! $page || ($page->post_status !== 'publish' && ! current_user_can('read_post', $page->ID)) || post_password_required($page)) {
            return null;
        }

        $content = apply_filters('the_content', $page->post_content);

        return has_block('sage/shop-products', $page)
            ? $content
            : $content . view('pkde-kit::partials.shop-archive-body', ['nested' => false])->render();
    }
}
