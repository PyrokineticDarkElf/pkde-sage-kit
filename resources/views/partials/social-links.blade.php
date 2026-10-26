{{--
  Round icon links to the site's social profiles (App\social_links(): the client_facebook, client_instagram, client_linkedin, client_x and
  client_youtube options). Prints nothing when none is set. 44px targets, an aria-label per link, decorative icons. Colours follow the
  surrounding text (currentColor), so it sits on the dark footer or a light band alike: pass `class` to change the ring/colour. The list
  always carries the class `pkde-social-links` (and an aria-label) so a site can scope a footer rule around it, for example
  `.content-info li:not(.pkde-social-links li) a { display: inline-block }`, since its links are display:grid on purpose.
--}}
@php($socialLinks = \App\social_links())
@if ($socialLinks)
  <ul class="pkde-social-links {{ $class ?? 'flex flex-wrap gap-3 pt-1' }}" aria-label="{{ __('Social media', 'sage') }}">
    @foreach ($socialLinks as $network => $link)
      <li>
        <a href="{{ $link['url'] }}" rel="noopener" aria-label="{{ $link['label'] }}" class="grid h-11 w-11 place-items-center rounded-full border border-current/25 transition hover:border-current">
          <svg viewBox="0 0 24 24" class="h-5 w-5" aria-hidden="true" focusable="false">
            @switch($network)
              @case('facebook')
                <path fill="currentColor" d="M13.5 21v-7.5h2.6l.4-3h-3V8.6c0-.9.3-1.5 1.6-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H8v3h2.5V21z" />
                @break
              @case('instagram')
                <g fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3.5" y="3.5" width="17" height="17" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.2" cy="6.8" r=".6" fill="currentColor" /></g>
                @break
              @case('linkedin')
                <g fill="currentColor"><rect x="4" y="9.5" width="3" height="10" /><circle cx="5.5" cy="5.6" r="1.8" /><path d="M10 9.5h2.9v1.4c.5-.9 1.6-1.7 3.2-1.7 3 0 3.9 1.9 3.9 4.6v5.7h-3v-5c0-1.3-.3-2.3-1.6-2.3-1.4 0-2.4 1-2.4 2.4v4.9h-3z" /></g>
                @break
              @case('x')
                <g fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M5 4.5l14 15M19 4.5l-14 15" /></g>
                @break
              @case('youtube')
                <g><rect x="3" y="6" width="18" height="12" rx="3.5" fill="none" stroke="currentColor" stroke-width="1.7" /><path d="M10.2 9.3l4.6 2.7-4.6 2.7z" fill="currentColor" /></g>
                @break
            @endswitch
          </svg>
        </a>
      </li>
    @endforeach
  </ul>
@endif
