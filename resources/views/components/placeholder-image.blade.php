@props([
  'ratio' => 'aspect-[4/3]',
  'label' => null,
])

<div {{ $attributes->merge(['class' => "{$ratio} flex flex-col items-center justify-center gap-2 rounded-2xl border border-neutral-200 bg-neutral-50 text-neutral-600"]) }}>
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-10 w-10">
    <rect x="3" y="3" width="18" height="18" rx="3" />
    <circle cx="9" cy="9" r="1.5" />
    <path d="M21 15l-5-5-6 6-2-2-5 5" />
  </svg>
  @if ($label)
    <span class="text-xs font-medium uppercase tracking-wide">{{ $label }}</span>
  @endif
</div>
