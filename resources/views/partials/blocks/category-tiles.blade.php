{{--
  Category photograph tiles — the "tiles" layout of the Product Categories block (product-categories/render.php): a large
  photograph with the name beneath it, with optional label overrides and extra tiles that link anywhere (a "Shop all" tile).
  The image is the category's own thumbnail (Products > Categories), else the most popular product's photograph
  (App\category_menu_image), else a placeholder. An image given as a URL (the fallback, an extra tile) goes through the media library when it is an
  upload (App\media_library_image: width, height, srcset, sizes). The default "Uncategorized" category is never a tile.

  The look is resources/css/category-tiles.css: --pkde-tile-* variables (columns, gaps, shape, corners, zoom, title), and the stable classes
  pkde-tiles / pkde-tile / pkde-tile__media / pkde-tile__title for a site that restyles the tiles from its own CSS. The picture's size is the filter
  pkde_kit/tile_image_size (default medium_large).

  Props: $terms (WP_Term[]), $labels (slug => label overrides), $extra (array of {label, url, image, alt}), $emptyMessage
--}}
@php($terms = array_values(array_filter((array) ($terms ?? []), fn ($term) => $term->slug !== 'uncategorized')))
@php($tileImageSize = (string) apply_filters('pkde_kit/tile_image_size', 'medium_large'))
@if (! empty($terms) || ! empty($extra))
  <div class="pkde-tiles">
    @foreach ($terms as $term)
      @php($label = trim((string) (($labels ?? [])[$term->slug] ?? '')) ?: $term->name)
      @php($thumbnail = (int) get_term_meta($term->term_id, 'thumbnail_id', true))
      @php($fallback = $thumbnail ? '' : \App\category_menu_image((int) $term->term_id))
      <div class="pkde-tile">
        {{-- The picture link repeats the name link below it: hidden from assistive tech and the tab order. --}}
        <a href="{{ get_term_link($term) }}" aria-hidden="true" tabindex="-1" class="pkde-tile__media">
          @if ($thumbnail)
            {!! wp_get_attachment_image($thumbnail, $tileImageSize, false, ['loading' => 'lazy', 'alt' => '']) !!}
          @elseif ($fallback)
            {!! \App\media_library_image($fallback, '', [], $tileImageSize) !!}
          @else
            <x-pkde-kit::placeholder-image ratio="h-full" label="{{ $label }}" />
          @endif
        </a>
        <h3 class="pkde-tile__title">
          <a href="{{ get_term_link($term) }}">{{ $label }}</a>
        </h3>
      </div>
    @endforeach

    @foreach (($extra ?? []) as $tile)
      @php($tileUrl = esc_url(str_starts_with($tile['url'], '/') ? home_url($tile['url']) : $tile['url']))
      <div class="pkde-tile">
        <a href="{{ $tileUrl }}" aria-hidden="true" tabindex="-1" class="pkde-tile__media">
          @if (! empty($tile['image']))
            {!! \App\media_library_image($tile['image'], (string) ($tile['alt'] ?? ''), [], $tileImageSize) !!}
          @else
            <x-pkde-kit::placeholder-image ratio="h-full" label="{{ $tile['label'] }}" />
          @endif
        </a>
        <h3 class="pkde-tile__title">
          <a href="{{ $tileUrl }}">{{ $tile['label'] }}</a>
        </h3>
      </div>
    @endforeach
  </div>
@else
  <p class="text-center text-neutral-600">{{ $emptyMessage ?? __('Nothing to show yet.', 'sage') }}</p>
@endif
