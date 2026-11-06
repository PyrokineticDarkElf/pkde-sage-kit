{{--
  The Sale and New badges of $product (App\product_badges(): when each shows, and the pkde_kit/product_badges filter). Used by the product card and
  the product page; the look is .pkde-badge in resources/css/shop-pages.css (--pkde-badge-* variables). $links (bool) links a badge to that view of the
  shop; without it, archives and searches link and curated rows do not. The badges sit in a relatively positioned box (the card's picture).
--}}
@foreach (\App\product_badges($product, $links ?? null) as $badge)
  @if ($badge['url'] !== '')
    <a href="{{ $badge['url'] }}" rel="nofollow" aria-label="{{ $badge['aria'] }}" class="pkde-badge pkde-badge--{{ $badge['key'] }}">{{ $badge['label'] }}</a>
  @else
    <span class="pkde-badge pkde-badge--{{ $badge['key'] }}">{{ $badge['label'] }}<span class="sr-only">: {{ $badge['name'] }}</span></span>
  @endif
@endforeach
