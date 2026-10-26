{{--
  The header's search, opt-in (`add_filter('pkde_kit/header_search', '__return_true')`, App\header_search_enabled()). Two parts, printed
  where the header puts them: `button` is the icon in the bar, `panel` the box under the bar it opens (kit.js toggles it; Escape closes it
  and returns focus to the icon; the menu panel closes first). Usage in the header: @include('pkde-kit::partials.header-search', ['part' => 'button', 'iconClass' => '...']).
--}}
@if (\App\header_search_enabled())
  @if (($part ?? 'button') === 'button')
    <button type="button" data-search-toggle aria-expanded="false" aria-controls="header-search-panel" class="{{ $iconClass ?? 'text-neutral-900 hover:text-neutral-600' }}" aria-label="{{ __('Search products', 'sage') }}">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" class="h-6 w-6" aria-hidden="true">
        <circle cx="11" cy="11" r="7" />
        <path d="m20 20-4-4" />
      </svg>
    </button>
  @else
    <div id="header-search-panel" class="header-search-panel hidden {{ $panelClass ?? 'border-t border-neutral-200/80 bg-white' }}">
      <div class="mx-auto max-w-content px-[var(--page-gutter,1.5rem)]">@include('pkde-kit::partials.product-search', ['id' => 'header-search'])</div>
    </div>
  @endif
@endif
