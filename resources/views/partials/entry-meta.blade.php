{{--
  $cardStyle only ever arrives here already set in the including view's own
  scope (Blade @include shares the caller's variables automatically) —
  content.blade.php sets it from a themed loop block's `cardStyle`
  attribute; content-single.blade.php/content-search.blade.php never do, so
  it's undefined there and this partial keeps its original neutral-500/700
  hierarchy unchanged. Only actually switches for 'tinted'/'bold' — the
  original hierarchy is *for* a plain white card/page, and neutral-500 date
  text against a saturated 'bold' card background is genuinely low-contrast
  (confirmed directly, not theoretical) the same way the card's own
  title/excerpt would have been before those were switched to
  --pkde-card-content/-accent — see partials/content.blade.php's own
  comment on $cardStyle.
--}}
@php($cardStyle ??= 'white')
<p @class([
  'text-sm',
  'text-neutral-500' => $cardStyle === 'white',
  'text-[color:var(--pkde-card-content)] opacity-75' => $cardStyle !== 'white',
])>
  <time class="dt-published" datetime="{{ get_post_time('c', true) }}">
    {{ get_the_date() }}
  </time>
  {{-- No author link when the author has no display name: an empty link has no accessible name (axe link-name). --}}
  @if (get_the_author() !== '')
    <span aria-hidden="true">&middot;</span>
    <span>{{ __('By', 'sage') }}</span>
    <a href="{{ get_author_posts_url(get_the_author_meta('ID')) }}" @class([
      'p-author h-card font-medium',
      'text-neutral-700 hover:text-neutral-900' => $cardStyle === 'white',
      'text-[color:var(--pkde-card-content)] hover:text-[color:var(--pkde-card-accent)]' => $cardStyle !== 'white',
    ])>
      {{ get_the_author() }}
    </a>
  @endif
  @if (! empty($showReadingTime))
    <span aria-hidden="true">&middot;</span>
    <span>{{ \App\ReadingTime::forPost() }}</span>
  @endif
</p>
