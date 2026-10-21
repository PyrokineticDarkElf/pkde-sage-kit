{{--
  Shared layout for the Accordion and FAQs blocks (same visual component,
  see resources/blocks/README.md's own note on why) — side-by-side, an even
  50/50 split, below $columnThreshold items (heading/description on the
  left, items on the right); above it, heading/description moves above the items, full width,
  and the items themselves reflow into a two-column newspaper-style layout
  (never three — kept to two even on very wide screens, so a single column
  never gets narrow enough to cramp an item's own content) instead of one
  long single-column list. Each Accordion Item's own <details> (see
  accordion-item/render.php) already carries its own border-b and
  break-inside-avoid, so it looks correct in either layout without this
  partial needing to know anything about its internal markup.

  Props: $heading (string, optional), $description (string, optional),
         $content (string, raw HTML — already-rendered Accordion Item
         children), $itemCount (int), $columnThreshold (int), $themed
         (bool, optional, default false) — see accordion/render.php's own
         comment; switches the heading/description/divider to
         --pkde-theme-content/-border the same way section-header.blade.php
         does for the loop blocks. Accordion Item itself isn't a template
         include (a genuinely separate block instance), so it can't receive
         this prop directly — see app.css's own
         `.pkde-section .wp-block-sage-accordion-item` rule for how it picks
         up the theme anyway, purely through CSS scoping.
--}}
@php($useColumns = $itemCount > $columnThreshold)
@php($hasHeader = $heading || $description)
@php($themed = $themed ?? false)

@if ($useColumns)
  <div>
    @if ($hasHeader)
      <div class="max-w-2xl">
        @if ($heading)
          <h2 @class([
            'font-display text-2xl font-semibold tracking-[-0.02em] sm:text-3xl',
            'text-[color:var(--pkde-theme-content)]' => $themed,
            'text-neutral-950' => ! $themed,
          ])>{{ $heading }}</h2>
        @endif
        @if ($description)
          <p @class([
            'mt-2',
            'text-[color:var(--pkde-theme-content)] opacity-75' => $themed,
            'text-neutral-600' => ! $themed,
          ])>{{ $description }}</p>
        @endif
      </div>
    @endif
    <div @class([
      'columns-1 gap-x-8 sm:columns-2',
      $hasHeader ? 'mt-8' : '',
      'border-t border-[color:var(--pkde-theme-border)]' => $themed,
      'border-t border-neutral-200' => ! $themed,
    ])>
      {!! $content !!}
    </div>
  </div>
@else
  <div class="lg:grid lg:grid-cols-2 lg:gap-x-12">
    @if ($hasHeader)
      <div class="lg:col-span-1">
        @if ($heading)
          <h2 @class([
            'font-display text-2xl font-semibold tracking-[-0.02em] sm:text-3xl',
            'text-[color:var(--pkde-theme-content)]' => $themed,
            'text-neutral-950' => ! $themed,
          ])>{{ $heading }}</h2>
        @endif
        @if ($description)
          <p @class([
            'mt-2',
            'text-[color:var(--pkde-theme-content)] opacity-75' => $themed,
            'text-neutral-600' => ! $themed,
          ])>{{ $description }}</p>
        @endif
      </div>
    @endif
    <div @class([
      $hasHeader ? 'mt-6 lg:col-span-1 lg:mt-0' : 'lg:col-span-2',
      'border-t border-[color:var(--pkde-theme-border)]' => $themed,
      'border-t border-neutral-200' => ! $themed,
    ])>
      {!! $content !!}
    </div>
  </div>
@endif
