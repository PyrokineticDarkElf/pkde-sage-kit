{{--
  The optional header slot of Site details: the first phone number (a tel: link, its number hidden on a narrow screen) and the social icons. Only printed when the site turns it
  on with the filter pkde_kit/header_site_details; the header view includes it with `iconClass` (the header's own icon colour). Prints nothing when the site has neither.
--}}
@php($headerPhone = \App\site_phones()[0] ?? null)
@if ($headerPhone || \App\social_links())
  <div class="pkde-header-details flex items-center gap-3 {{ $iconClass ?? '' }}">
    @if ($headerPhone)
      <a href="{{ \App\site_tel_href($headerPhone['number']) }}" class="pkde-header-phone inline-flex items-center gap-2 text-sm font-medium">
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 4h3.5l1.5 4-2 1.3a11 11 0 0 0 5.7 5.7L15 13l4 1.5V18a2 2 0 0 1-2 2C9.8 20 4 14.2 4 7a2 2 0 0 1 1-1.7" /></svg>
        <span class="hidden lg:inline">{{ ($headerPhone['display'] ?? '') !== '' ? $headerPhone['display'] : $headerPhone['number'] }}</span>
        <span class="sr-only lg:hidden">{{ __('Call us', 'sage') }}</span>
      </a>
    @endif
    @if (\App\social_links())
      <div class="hidden md:block">@include('pkde-kit::partials.social-links', ['class' => 'flex items-center gap-2 [&_a]:h-9 [&_a]:w-9'])</div>
    @endif
  </div>
@endif
