@if (count($headings ?? []) > 1)
  <nav data-toc class="rounded-2xl border border-neutral-200 p-5 lg:sticky lg:top-24" aria-label="{{ __('Table of contents', 'sage') }}">
    <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('On this page', 'sage') }}</p>
    <ul class="mt-3 space-y-2 text-sm">
      @foreach ($headings as $heading)
        <li style="padding-left: {{ ($heading['level'] - 2) * 0.75 }}rem">
          <a
            href="#{{ $heading['id'] }}"
            data-toc-link="{{ $heading['id'] }}"
            class="block text-neutral-600 transition hover:text-neutral-900 data-[active=true]:font-semibold data-[active=true]:text-brand-700"
          >
            {{ $heading['text'] }}
          </a>
        </li>
      @endforeach
    </ul>
  </nav>
@endif
