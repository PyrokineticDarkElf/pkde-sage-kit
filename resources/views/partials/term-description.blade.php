{{--
  The description of the category, tag or other taxonomy term being listed, above the posts (blog/category and blog/tag). Nothing when the term has none.
  Since kit 2026.11.05.3 the page header prints this text as its intro line, so this partial prints nothing while the header has an intro (it never shows twice); the kit's
  category and tag views no longer include it. The text is printed through wp_kses_post: a term description is stored through WordPress's kses for anyone without unfiltered_html.
--}}
@php($termDescription = (is_category() || is_tag() || is_tax()) && \App\page_header_intro() === '' ? term_description() : '')
@if ($termDescription !== '')
  <div class="{{ \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)] pt-8', 'term-description') }}">
    <div class="prose-content max-w-3xl text-neutral-600">{!! wp_kses_post($termDescription) !!}</div>
  </div>
@endif
