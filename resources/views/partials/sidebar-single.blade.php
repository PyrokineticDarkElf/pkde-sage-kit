<aside class="space-y-8">
  @include('pkde-kit::partials.toc', ['headings' => $headings ?? []])
  @include('pkde-kit::partials.author-box')
  @include('pkde-kit::partials.related-posts')
  @include('pkde-kit::partials.sidebar-terms', ['taxonomy' => 'category', 'heading' => __('Categories', 'sage')])
  @include('pkde-kit::partials.sidebar-terms', ['taxonomy' => 'post_tag', 'heading' => __('Tags', 'sage')])
</aside>
