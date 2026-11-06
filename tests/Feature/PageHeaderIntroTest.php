<?php

// app/page-header.php: page_header_intro() takes its context as an argument, so these tests need no WordPress.
require_once dirname(__DIR__, 2) . '/app/page-header.php';

use function App\page_header_intro;

/** A context like page_header_context() builds it, with a filter table in place of apply_filters. */
function intro_ctx(array $over = [], array $filters = []): array
{
    return array_merge([
        'object' => null, 'home' => false, 'excerpt' => '', 'term' => false, 'description' => '', 'product' => false,
        'html' => fn() => '',
        'filter' => fn($name, $value, ...$args) => isset($filters[$name]) ? $filters[$name]($value, ...$args) : $value,
    ], $over);
}

function term_ctx(string $description, bool $product = false, array $filters = []): array
{
    return intro_ctx(['term' => true, 'description' => $description, 'product' => $product, 'object' => (object) [], 'html' => fn() => "<p>$description</p>"], $filters);
}


it('uses the posts page excerpt, escaped, and nothing when the excerpt is empty', function () {
    expect(page_header_intro(intro_ctx(['home' => true, 'excerpt' => 'Facts & insights from the world of clocks.'])))->toBe('<p>Facts &amp; insights from the world of clocks.</p>')
        ->and(page_header_intro(intro_ctx(['home' => true, 'excerpt' => ''])))->toBe('');
});

it('uses a blog term\'s description as the intro, and nothing when it has none', function () {
    expect(page_header_intro(term_ctx('News from the workshop.')))->toBe('<p>News from the workshop.</p>')
        ->and(page_header_intro(term_ctx('')))->toBe('');
});

it('leaves a product term\'s description to the "Read more" toggle, so it never shows twice, unless the toggle is switched off', function () {
    expect(page_header_intro(term_ctx('About these clocks.', true)))->toBe('')
        ->and(page_header_intro(term_ctx('About these clocks.', true, ['pkde_kit/shop_term_toggle' => fn($show) => false])))->toBe('<p>About these clocks.</p>');
});

it('lets a site replace the intro or switch it off with the pkde_kit/page_header_intro filter', function () {
    expect(page_header_intro(term_ctx('News.', false, ['pkde_kit/page_header_intro' => fn($html, $object) => ''])))->toBe('')
        ->and(page_header_intro(term_ctx('News.', false, ['pkde_kit/page_header_intro' => fn($html, $object) => '<p>Ours.</p>'])))->toBe('<p>Ours.</p>');
});

it('prints the intro under the H1 and styles it with --pkde-page-header-intro* variables', function () {
    $root = dirname(__DIR__, 2);
    $view = file_get_contents("$root/resources/views/partials/page-header.blade.php");
    $css = file_get_contents("$root/resources/css/page-header.css");

    expect($view)->toContain('page-header__intro', '$intro')
        ->and($css)->toContain('--pkde-page-header-intro:', '--pkde-page-header-intro-size:', '--pkde-page-header-intro-max-width:', '.page-header__intro')
        ->and($view)->toContain('App\\page_header_intro')
        ->and(file_get_contents("$root/resources/views/category.blade.php"))->not->toContain('partials.term-description');
});

/**
 * Renders the REAL page header partial with Illuminate's view factory. Kit 2026.11.05.3 printed an empty intro element under every header
 * because the Post composer handed `intro()` over as a public method (Acorn passes those as callable objects, always "set").
 */
function render_page_header(array $data): string
{
    $root = dirname(__DIR__, 2);
    $stubs = sys_get_temp_dir() . '/pkde-header-' . bin2hex(random_bytes(4));
    mkdir("$stubs/partials", 0777, true);
    file_put_contents("$stubs/partials/breadcrumbs.blade.php", '<nav class="breadcrumbs">crumbs</nav>');

    if (! function_exists('wp_kses_post')) {
        eval('function wp_kses_post($html) { return $html; }');
    }
    if (! function_exists('App\container_class')) {
        eval('namespace App; function container_class($default, $context = "") { return $default; }');
    }

    $files = new Illuminate\Filesystem\Filesystem();
    $compiled = "$stubs/compiled";
    mkdir($compiled);
    $blade = new Illuminate\View\Compilers\BladeCompiler($files, $compiled);
    $resolver = new Illuminate\View\Engines\EngineResolver();
    $resolver->register('blade', fn() => new Illuminate\View\Engines\CompilerEngine($blade, $files));
    $finder = new Illuminate\View\FileViewFinder($files, []);
    $finder->addNamespace('pkde-kit', ["$stubs", "$root/resources/views"]);
    $factory = new Illuminate\View\Factory($resolver, $finder, new Illuminate\Events\Dispatcher());
    $factory->addExtension('blade.php', 'blade');
    $html = $factory->make('pkde-kit::partials.page-header', $data)->render();

    $files->deleteDirectory($stubs);

    return $html;
}

it('renders a page header with no intro element when the intro is empty, and one when it has text', function () {
    $none = render_page_header(['title' => 'Our Blog', 'intro' => '']);

    expect($none)->toContain('<h1', 'Our Blog')->not->toContain('page-header__intro');

    $with = render_page_header(['title' => 'Our Blog', 'intro' => '<p>Short notes.</p>']);

    expect($with)->toContain('<div class="page-header__intro mt-4"><p>Short notes.</p></div>');
});

it('never hands the intro to the view as a composer method (Acorn passes public methods as always-set objects)', function () {
    $composer = file_get_contents(dirname(__DIR__, 2) . '/app/View/Composers/Post.php');

    expect($composer)->not->toMatch('/function\s+intro\s*\(/')
        ->and($composer)->not->toMatch('/function\s+with\s*\(/');
});
