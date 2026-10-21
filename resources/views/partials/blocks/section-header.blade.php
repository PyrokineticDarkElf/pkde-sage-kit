{{--
  Shared section header for every "loop" block (Recent Posts, Featured
  Posts, Product Categories, Brands, Recent Products, Best Selling
  Products, Featured Products) — a block-owned H2 + description, with a
  "View all" link top-right on the same row as the heading. This makes each
  block a complete section on its own, so a client no longer has to
  remember to also place a separate Heading/Paragraph block before it just
  to give the section a title — see each block's own edit.jsx for the
  Inspector Controls fields, and block.json for the default heading/
  description text.

  block-demo still has its own external `<!-- wp:heading -->`/
  `<!-- wp:paragraph -->` before each block on purpose (see bin/setup's
  DEMO_CONTENT) — that's deliberately left in place as a reference showing
  native heading/paragraph blocks respecting content-grid's width alongside
  this block-owned version, not a leftover that should be removed.

  Props: $heading (string), $description (string, optional),
         $viewAllUrl (string|null), $viewAllLabel (string),
         $themed (bool, optional, default false) — true when the calling
         block's own render.php applied App\pkde_loop_theme_classes() to its
         wrapper, so this heading/description/link need the same
         --pkde-theme-content/--pkde-theme-highlight-driven colours Hero and
         CTA use instead of the plain-white-page neutral palette below —
         see App\pkde_loop_theme_classes()'s own comment (app/blocks.php).
--}}
<div class="mb-8">
  <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2">
    <h2 @class([
      'font-display text-2xl font-semibold tracking-[-0.02em] sm:text-3xl',
      'text-[color:var(--pkde-theme-content)]' => $themed ?? false,
      'text-neutral-950' => ! ($themed ?? false),
    ])>
      {{ $heading }}
    </h2>
    @if ($viewAllUrl)
      <a href="{{ $viewAllUrl }}" @class([
        'inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold transition',
        'text-[color:var(--pkde-theme-content)] opacity-90 hover:opacity-100 hover:text-[color:var(--pkde-theme-highlight)]' => $themed ?? false,
        'text-neutral-900 hover:text-brand-700' => ! ($themed ?? false),
      ])>
        {{ $viewAllLabel }}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
        </svg>
      </a>
    @endif
  </div>
  @if ($description)
    <p @class([
      'mt-2 max-w-2xl',
      'text-[color:var(--pkde-theme-content)] opacity-75' => $themed ?? false,
      'text-neutral-600' => ! ($themed ?? false),
    ])>{{ $description }}</p>
  @endif
</div>
