<?php

/**
 * Shop products: the shop's own product archive (filters, sorting, pagination) on the Shop page; nothing anywhere else.
 *
 * @var array $attributes
 */

if (! function_exists('is_shop') || ! is_shop()) {
    return;
}

printf('<div %s>%s</div>', get_block_wrapper_attributes(), view('pkde-kit::partials.shop-archive-body', ['nested' => true])->render());
