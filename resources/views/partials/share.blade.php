{{--
  The share row under a post: plain links to each network's share page (opened in a new tab, no script and no tracker from this site) and a
  "Copy link" button (resources/js/kit.js, [data-share-copy]). Networks: pkde_kit/share_links. Off: pkde_kit/single_show 'share'.
--}}
@php($shareLinks = \App\share_links(get_permalink(), wp_strip_all_tags(get_the_title())))
@if ($shareLinks)
  <div class="post-share">
    <p class="post-share__label">{{ __('Share this post', 'sage') }}</p>
    <ul class="post-share__list">
      @foreach ($shareLinks as $label => $url)
        <li>
          <a href="{{ $url }}" @if (! str_starts_with($url, 'mailto:')) target="_blank" rel="noopener noreferrer" @endif aria-label="{{ sprintf(__('Share on %s', 'sage'), $label) }}">{{ $label }}</a>
        </li>
      @endforeach
      <li>
        <button type="button" data-share-copy data-share-url="{{ get_permalink() }}" data-copied-label="{{ __('Link copied', 'sage') }}">{{ __('Copy link', 'sage') }}</button>
      </li>
    </ul>
    <p class="sr-only" role="status" data-share-status></p>
  </div>
@endif
