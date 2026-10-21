{{--
  Category photograph tiles — the "tiles" layout of the Product Categories block (product-categories/render.php): a large
  photograph with the name beneath it, with optional label overrides and extra tiles that link anywhere (a "Shop all" tile).
  The image is the category's own thumbnail (Products > Categories), else the most popular product's photograph
  (App\category_menu_image), else a placeholder.

  Props: $terms (WP_Term[]), $labels (slug => label overrides), $extra (array of {label, url, image, alt}), $emptyMessage
--}}
@if (! empty($terms) || ! empty($extra))
  <div class="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($terms as $term)
      @php($label = trim((string) (($labels ?? [])[$term->slug] ?? '')) ?: $term->name)
      @php($thumbnail = (int) get_term_meta($term->term_id, 'thumbnail_id', true))
      @php($fallback = $thumbnail ? '' : \App\category_menu_image((int) $term->term_id))
      <div class="group">
        {{-- The picture link repeats the name link below it: hidden from assistive tech and the tab order. --}}
        <a href="{{ get_term_link($term) }}" aria-hidden="true" tabindex="-1" class="block aspect-[4/3] overflow-hidden rounded-2xl bg-neutral-100">
          @if ($thumbnail)
            {!! wp_get_attachment_image($thumbnail, 'medium_large', false, ['loading' => 'lazy', 'alt' => '', 'class' => 'h-full w-full object-cover transition duration-500 ease-out group-hover:scale-[1.03]']) !!}
          @elseif ($fallback)
            <img src="{{ $fallback }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-500 ease-out group-hover:scale-[1.03]">
          @else
            <x-pkde-kit::placeholder-image ratio="aspect-[4/3]" label="{{ $label }}" />
          @endif
        </a>
        <h3 class="mt-4 font-display text-lg font-semibold tracking-[-0.02em]">
          <a href="{{ get_term_link($term) }}" class="transition hover:text-brand-700">{{ $label }}</a>
        </h3>
      </div>
    @endforeach

    @foreach (($extra ?? []) as $tile)
      @php($tileUrl = str_starts_with($tile['url'], '/') ? home_url($tile['url']) : $tile['url'])
      <div class="group">
        <a href="{{ $tileUrl }}" aria-hidden="true" tabindex="-1" class="block aspect-[4/3] overflow-hidden rounded-2xl bg-neutral-100">
          @if (! empty($tile['image']))
            <img src="{{ esc_url($tile['image']) }}" alt="{{ esc_attr($tile['alt'] ?? '') }}" loading="lazy" class="h-full w-full object-cover transition duration-500 ease-out group-hover:scale-[1.03]">
          @else
            <x-pkde-kit::placeholder-image ratio="aspect-[4/3]" label="{{ $tile['label'] }}" />
          @endif
        </a>
        <h3 class="mt-4 font-display text-lg font-semibold tracking-[-0.02em]">
          <a href="{{ $tileUrl }}" class="transition hover:text-brand-700">{{ $tile['label'] }}</a>
        </h3>
      </div>
    @endforeach
  </div>
@else
  <p class="text-center text-neutral-600">{{ $emptyMessage ?? __('Nothing to show yet.', 'sage') }}</p>
@endif
