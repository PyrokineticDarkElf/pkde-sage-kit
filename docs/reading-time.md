# Reading time

## Overview

Reading time is a first-class theme service provided by
`app/ReadingTime.php`. It is not a shortcode and does not add any public
WordPress content syntax. This keeps the display controlled by templates and
prevents editors from needing to maintain a calculated value in post content.

## Display location

Single blog posts display the estimate in their metadata row, after the
published date and author. The integration lives in
`resources/views/partials/content-single.blade.php`, which passes
`showReadingTime => true` to the shared `entry-meta` partial.

Archive cards, related posts, pages, and WooCommerce products do not show a
reading-time label by default.

## API

```php
use App\ReadingTime;

// Uses the current global post.
echo ReadingTime::forPost();

// Uses a specific WP_Post instance.
echo ReadingTime::forPost($post);
```

`ReadingTime::forPost()` returns an empty string when no valid `WP_Post` is
available. Templates can therefore call it safely in an optional metadata slot.

## Calculation

1. The post's stored content passes through `the_content`, so rendered blocks,
   embedded content, and image markup are considered.
2. HTML is stripped and the remaining text is counted at **250 words per
   minute**.
3. Each rendered `<img>` adds a viewing allowance:
   - images 1–10 add 12, 11, 10, 9, 8, 7, 6, 5, 4, and 3 seconds respectively;
   - every image after the tenth adds 3 seconds.
4. Text and image time are combined, rounded up to a whole minute, and never
   return less than one minute.

The final label uses WordPress's `_n()` translation helper and the `sage` text
domain, producing either “1 minute read” or “N minutes read”.

## Performance

The rendered-content calculation is cached by post ID for the duration of the
current PHP request. Reusing it in another template during the same request
does not process the content again. It is intentionally not persisted: a post
edit, plugin content filter, or block update is reflected immediately.

## Extending it

Keep calculation rules in `App\ReadingTime`; avoid duplicating word/image
counting logic in Blade templates. If a client needs a different reading speed
or image allowance, change the service constants/logic and update this guide.
If reading time is ever needed in a REST response or custom block, call the
same `ReadingTime::forPost()` API rather than introducing a shortcode.
