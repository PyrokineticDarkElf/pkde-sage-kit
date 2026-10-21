# Author pages

`resources/views/author.blade.php` — WordPress's normal template hierarchy
(`author-{nicename} → author-{id} → author → archive → index`), no custom query needed since the
main query is already scoped to that author's posts. Author info comes first and prominently —
large avatar, name, full bio, website link if set — in the same visual language as the About
page's intro section, before a secondary "Posts by {name}" section that reuses the existing
`partials/post-grid.blade.php` (grid + pagination, unchanged).
