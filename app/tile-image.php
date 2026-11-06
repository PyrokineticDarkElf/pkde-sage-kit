<?php

/**
 * The image of a category tile (tcs's, TCS-84, as a kit helper so any site's tiles can call it; the kit's own default tiles are unchanged).
 * A tile photo goes through the media library whenever it is one of its files (an attachment id, or the URL of an original or a resized upload,
 * "photo-300x200.jpg"), so it gets width and height (no layout shift), srcset and sizes; any other URL stays a plain img. The photo of a category
 * is its own thumbnail, else the most popular product's photograph (\App\category_menu_image()).
 *
 *   {!! \App\category_tile_image($thumbnailId, $thumbnailId ? '' : \App\category_menu_image($term->term_id)) !!}
 *   {!! \App\category_tile_image(0, $tile['image'], $tile['alt'], 'my-tile__img') !!}
 */

namespace App;

/** The URL of the original of a resized upload ("photo-768x512.jpg" becomes "photo.jpg"); other URLs are returned as they are. */
function upload_original_url(string $url): string
{
    return (string) preg_replace('/-\d+x\d+(?=\.\w+$)/', '', $url);
}

/**
 * The tile image markup: <img> through the media library when the file is one, else a plain lazy <img>, else ''.
 *
 * @param  string  $class  the class on the img (tcs's tiles use category-tile__img)
 * @param  string  $sizes  the sizes attribute (a tile is at most 14rem wide, about two across on a phone)
 */
function category_tile_image(int $attachmentId, string $url = '', string $alt = '', string $class = 'category-tile__img', string $sizes = '(min-width: 1024px) 14rem, 40vw'): string
{
    $full = str_starts_with($url, '/') ? home_url($url) : $url;
    $id = $attachmentId ?: ($full !== '' ? ((int) attachment_url_to_postid($full) ?: (int) attachment_url_to_postid(upload_original_url($full))) : 0);
    $attrs = ['loading' => 'lazy', 'class' => $class, 'sizes' => $sizes] + ($alt !== '' ? ['alt' => $alt] : []);

    if ($id) {
        return (string) wp_get_attachment_image($id, 'medium_large', false, $attrs);
    }

    return $url !== '' ? '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" loading="lazy" class="' . esc_attr($class) . '" />' : '';
}
