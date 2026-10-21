@php
  $authorId = get_the_author_meta('ID');
  $bio = get_the_author_meta('description');
@endphp

<div class="rounded-2xl border border-neutral-200 p-5">
  <div class="flex items-center gap-3">
    {!! get_avatar($authorId, 64, '', '', ['class' => 'rounded-full']) !!}
    <div>
      <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('Written by', 'sage') }}</p>
      <a href="{{ get_author_posts_url($authorId) }}" class="font-display text-base font-bold text-neutral-900 hover:text-neutral-600">
        {{ get_the_author() }}
      </a>
    </div>
  </div>

  @if ($bio)
    <p class="mt-3 text-sm text-neutral-600">{{ wp_trim_words($bio, 20) }}</p>
  @endif
</div>
