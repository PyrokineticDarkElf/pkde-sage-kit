{{-- One testimonial. Props: $quote (quote, name, role, rating), $stars (bool), $star (the star SVG). --}}
<figure class="pkde-quote">
  @if ($stars && $quote['rating'] > 0)
    <div class="pkde-stars" role="img" aria-label="{{ esc_attr(sprintf(__('Rated %d out of 5', 'sage'), $quote['rating'])) }}">@for ($i = 0; $i < $quote['rating']; $i++){!! $star !!}@endfor</div>
  @endif
  {{-- data-pkde-label: the accessible name resources/js/quote-scroll.js gives the box when a long review scrolls (reviews only); nothing changes for a text that fits. --}}
  <blockquote data-pkde-label="{{ esc_attr($quote['name'] !== '' ? sprintf(__('Review by %s', 'sage'), $quote['name']) : __('Review', 'sage')) }}">{!! wp_kses_post(wpautop(esc_html($quote['quote']))) !!}</blockquote>
  @if ($quote['name'] !== '' || $quote['role'] !== '')
    <figcaption>
      @if ($quote['name'] !== '')<span class="pkde-quote__name">{{ $quote['name'] }}</span>@endif{{ $quote['name'] !== '' && $quote['role'] !== '' ? ', ' : '' }}@if ($quote['role'] !== '')<span class="pkde-quote__role">{{ $quote['role'] }}</span>@endif
    </figcaption>
  @endif
</figure>
