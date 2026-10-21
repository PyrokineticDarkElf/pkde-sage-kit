{{--
  Category card grid — used by the Product Categories block
  (product-categories/render.php) and the standalone Categories directory
  page (template-categories.blade.php). Title and product count overlay the
  bottom of the image itself, unlike Brand cards (brand-cards.blade.php),
  which are image-only with no visible caption — a category card needs the
  name/count to actually browse by, a brand card's whole point is usually
  just the logo.

  Props: $terms (WP_Term[]), $labels (slug => label overrides, optional), $emptyMessage (string, optional)
--}}
@if (! empty($terms))
  <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($terms as $term)
      @php($imageId = get_term_meta($term->term_id, 'thumbnail_id', true))
      @php($label = trim((string) (($labels ?? [])[$term->slug] ?? '')) ?: $term->name)
      <a href="{{ get_term_link($term) }}" class="group relative block aspect-[4/3] overflow-hidden rounded-2xl bg-neutral-100">
        @if ($imageId)
          <img
            src="{{ wp_get_attachment_image_url($imageId, 'medium_large') }}"
            alt="{{ $label }}"
            class="h-full w-full object-cover transition duration-500 ease-out group-hover:scale-[1.03]"
          />
        @else
          <x-pkde-kit::placeholder-image ratio="aspect-[4/3]" label="{{ $label }}" />
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-neutral-950/80 via-neutral-950/10 to-transparent" aria-hidden="true"></div>
        <div class="absolute inset-x-0 bottom-0 p-4">
          <p class="font-semibold text-white">{{ $label }}</p>
          @if ($term->count)
            <p class="mt-0.5 text-sm text-white/80">
              {{ sprintf(_n('%d product', '%d products', $term->count, 'sage'), $term->count) }}
            </p>
          @endif
        </div>
      </a>
    @endforeach
  </div>
@else
  <p class="text-center text-neutral-600">{{ $emptyMessage ?? __('Nothing to show yet.', 'sage') }}</p>
@endif
