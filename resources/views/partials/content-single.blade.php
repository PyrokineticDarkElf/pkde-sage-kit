{{--
  The blog single (tcs's layout, the kit default - owner via MGR-66): header (breadcrumbs, category pills, title, meta), the featured image as a
  prominent hero, the text with its contents rail, then tags, share, the author box, previous/next, related and recent posts, and the Article schema.
  A site does NOT copy this view: it changes the look with the --pkde-post-* variables (resources/css/post-single.css), switches a section off with
  the pkde_kit/single_show filter, and adds to it with the pkde_kit/single_* actions. All of it: docs/blog-single.md.
--}}
<article @php(post_class('h-entry'))>
  @php($post = get_post())
  @php($toc = \App\TableOfContents::fromContent(apply_filters('the_content', get_the_content())))
  @php($show = fn (string $section) => \App\single_show($section, $post))
  @php(do_action('pkde_kit/single_before_hero', $post))

  <header class="{{ \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)]', 'content-single') }} post-header">
    @if ($show('breadcrumbs'))
      @include('pkde-kit::partials.breadcrumbs')
    @endif
    @if ($show('categories') && ($postCategories = get_the_category()))
      <div class="post-header__cats">
        @include('pkde-kit::partials.term-pills', ['terms' => $postCategories])
      </div>
    @endif
    <h1 class="p-name">{!! $title !!}</h1>
    @if ($show('meta'))
      @php(do_action('pkde_kit/single_before_meta', $post))
      <div class="post-header__meta">
        @include('pkde-kit::partials.entry-meta', ['showReadingTime' => true])
      </div>
      @php(do_action('pkde_kit/single_after_meta', $post))
    @endif
  </header>

  @if ($show('hero') && has_post_thumbnail())
    <figure class="{{ \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)]', 'content-single') }} post-hero">
      {!! get_the_post_thumbnail(null, 'large', ['class' => 'post-hero__img', 'fetchpriority' => 'high']) !!}
    </figure>
  @endif
  @php(do_action('pkde_kit/single_after_hero', $post))

  <div class="{{ \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)]', 'content-single') }} post-body">
    <div class="post-body__main">
      @php(do_action('pkde_kit/single_before_content', $post))
      <div class="e-content prose-content post-body__text">
        {!! $toc['content'] !!}

        @if ($pagination())
          <nav class="page-nav mt-10" aria-label="Page">
            {!! $pagination !!}
          </nav>
        @endif
      </div>
      @php(do_action('pkde_kit/single_after_content', $post))
    </div>

    <div class="post-rail">
      @php(do_action('pkde_kit/single_before_sidebar', $post))
      @if ($show('toc'))
        @include('pkde-kit::partials.toc', ['headings' => $toc['headings']])
      @endif
      @php(do_action('pkde_kit/single_after_sidebar', $post))
    </div>
  </div>

  <div class="{{ \App\container_class('mx-auto max-w-content px-[var(--page-gutter,1.5rem)]', 'content-single') }} post-after">
    @if ($show('share'))
      @include('pkde-kit::partials.share')
    @endif
    @if ($show('navigation'))
      @include('pkde-kit::partials.post-navigation')
    @endif
    @php(do_action('pkde_kit/single_before_related', $post))
    @if ($show('author_box') || $show('related'))
      <div class="post-after__grid">
        @if ($show('author_box'))
          @include('pkde-kit::partials.author-box')
        @endif
        @if ($show('related'))
          @include('pkde-kit::partials.related-posts')
        @endif
      </div>
    @endif
    @if ($show('recent'))
      @include('pkde-kit::partials.recent-posts')
    @endif
    @if ($show('tags') && ($postTags = get_the_tags()))
      <div class="post-after__terms">
        @include('pkde-kit::partials.term-pills', ['terms' => $postTags])
      </div>
    @endif
    @php(do_action('pkde_kit/single_after_related', $post))
  </div>

  @if ($show('schema') && ! class_exists('RankMath'))
    @php($schema = \App\current_article_schema())
    @if ($schema)
      {{-- Rank Math prints its own Article schema when it is active; this one is for sites without it (never both). --}}
      <script type="application/ld+json">{!! \App\schema_json($schema) !!}</script>
    @endif
  @endif
</article>
