{{--
  Text and image. Props: $wrapper (block wrapper attributes, escaped), $eyebrow, $content (the rendered inner blocks), $images (list of <img>
  HTML from \App\block_image(), 0 to 3), $mosaic (three images: the first tall, two stacked beside it).
--}}
<div {!! $wrapper !!}>
  <div @class(['pkde-tai__grid', 'pkde-split' => $images !== []])>
    <div class="pkde-tai__content">
      @if ($eyebrow !== '')
        <p class="pkde-eyebrow">{{ $eyebrow }}</p>
      @endif
      {!! $content !!}
    </div>
    @if ($images !== [])
      <div class="pkde-split__media pkde-tai__media">
        @if ($mosaic)
          <div class="pkde-tai__mosaic">
            @foreach ($images as $image){!! $image !!}@endforeach
          </div>
        @else
          {!! $images[0] !!}
        @endif
        @if (($badgeValue ?? '') !== '')
          <div class="pkde-tai__badge">
            <strong>{{ $badgeValue }}</strong>
            @if (($badgeLabel ?? '') !== '')<span>{{ $badgeLabel }}</span>@endif
          </div>
        @endif
      </div>
    @endif
  </div>
</div>
