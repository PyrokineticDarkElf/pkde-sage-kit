{{-- List-style card: small square image left, title + a clamped excerpt right (resources/css/clamp.css). --}}
<a href="{{ get_permalink() }}" class="group flex gap-4">
  @if (has_post_thumbnail())
    <div class="aspect-square w-20 flex-none overflow-hidden rounded-xl bg-neutral-100">
      {!! get_the_post_thumbnail(null, 'thumbnail', ['class' => 'h-full w-full object-cover pkde-zoom']) !!}
    </div>
  @else
    <x-pkde-kit::placeholder-image ratio="aspect-square" class="w-20 flex-none" />
  @endif

  <div class="min-w-0 self-center">
    <p class="pkde-related__title font-display text-sm font-semibold leading-snug text-neutral-900 transition group-hover:text-brand-700">
      {{ get_the_title() }}
    </p>
    <p class="pkde-related__excerpt mt-1 text-xs text-neutral-500">{{ \App\card_excerpt() }}</p>
  </div>
</a>
