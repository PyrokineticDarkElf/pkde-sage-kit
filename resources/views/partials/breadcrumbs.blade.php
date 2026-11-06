{{--
  The breadcrumb trail, owned by the kit (app/breadcrumbs.php): Rank Math's trail forced to the standard (" / ", "Home", the page as the last crumb),
  or the kit's own trail in the same markup when Rank Math's breadcrumbs are off, or WooCommerce's crumbs on shop pages. Style the markup directly
  (the nav carries class rank-math-breadcrumb); do not nest a second <nav>.
--}}
@php($trail = \App\breadcrumb_trail_html())
@if ($trail !== '')
  <div class="breadcrumbs text-xs text-neutral-600 [&_.rank-math-breadcrumb]:block [&_a]:hover:text-neutral-900">
    {!! $trail !!}
  </div>
@endif
