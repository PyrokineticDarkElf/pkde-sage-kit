{{--
  A block pattern in a bare page (app/pattern-preview.php): the theme's fonts, CSS and JS and the pattern's blocks, no site header or footer. Development only.
  Props: $html (the pattern's blocks, rendered), $brandCss (a brand's CSS text from the pattern library, or '').
--}}
<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Pattern preview</title>
    @fonts
    @php(wp_head())
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($brandCss !== '')
      <style id="pkde-pattern-brand">{!! $brandCss !!}</style>
    @endif
  </head>
  <body @php(body_class('bg-white text-neutral-900 antialiased pkde-pattern-preview'))>
    @php(wp_body_open())
    <main class="page-content prose-content content-grid">{!! $html !!}</main>
    @php(wp_footer())
  </body>
</html>
