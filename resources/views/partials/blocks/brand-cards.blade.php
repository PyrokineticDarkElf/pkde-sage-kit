{{--
  Brand card grid — used by the Brands block (brands/render.php) and the
  standalone Brands directory page (template-brands.blade.php). Image only,
  no visible caption — the brand's name lives in the image's own `alt` text
  for accessibility, not as on-page text, since a brand card's whole point
  is usually just the logo. A brand with no logo falls back to its name as
  a centered heading *in place of* the image, not below it — unlike
  Category cards (category-cards.blade.php), which always show a visible
  name/count caption overlaid on the image.

  Props: $terms (WP_Term[]), $emptyMessage (string, optional)
--}}
@if (! empty($terms))
  <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($terms as $term)
      @php($imageId = get_term_meta($term->term_id, 'thumbnail_id', true))
      <a href="{{ get_term_link($term) }}" class="group block aspect-[4/3] overflow-hidden rounded-2xl bg-neutral-100">
        @if ($imageId)
          <img
            src="{{ wp_get_attachment_image_url($imageId, 'medium_large') }}"
            alt="{{ $term->name }}"
            class="h-full w-full object-cover transition duration-500 ease-out group-hover:scale-[1.03]"
          />
        @else
          <div class="flex h-full w-full items-center justify-center p-4">
            <h3 class="text-center font-display text-lg font-semibold text-neutral-700">{{ $term->name }}</h3>
          </div>
        @endif
      </a>
    @endforeach
  </div>
@else
  <p class="text-center text-neutral-600">{{ $emptyMessage ?? __('Nothing to show yet.', 'sage') }}</p>
@endif
