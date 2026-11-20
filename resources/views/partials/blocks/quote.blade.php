{{-- One testimonial (WP-100). Props: $quote (quote, name, role, rating), $stars (bool), $star (the star SVG). --}}
<figure class="pkde-quote">
  @if ($stars && $quote['rating'] > 0)
    <div class="pkde-stars" role="img" aria-label="{{ esc_attr(sprintf(__('Rated %d out of 5', 'sage'), $quote['rating'])) }}">@for ($i = 0; $i < $quote['rating']; $i++){!! $star !!}@endfor</div>
  @endif
  <blockquote>{!! wp_kses_post(wpautop(esc_html($quote['quote']))) !!}</blockquote>
  @if ($quote['name'] !== '' || $quote['role'] !== '')
    <figcaption>
      @if ($quote['name'] !== '')<span class="pkde-quote__name">{{ $quote['name'] }}</span>@endif{{ $quote['name'] !== '' && $quote['role'] !== '' ? ', ' : '' }}@if ($quote['role'] !== '')<span class="pkde-quote__role">{{ $quote['role'] }}</span>@endif
    </figcaption>
  @endif
</figure>
