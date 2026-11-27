<?php

/**
 * The site's default card look for the loop blocks: what a block shows when it names a look, when it says `plain`, and when it names nothing.
 */
use function App\card_defaults_read;
use function App\card_look_for;

it('shows the look a block names, when it is one the block has', function () {
    expect(card_look_for('product-loop', 'tiles', ['tiles', 'centred']))->toBe('tiles')
        ->and(card_look_for('latest-posts', 'overlay', ['panel', 'overlay', 'label']))->toBe('overlay');
});

it('shows the standard card for plain and for a look the block does not have, when the site has no default', function () {
    expect(card_look_for('product-loop', 'plain', ['tiles']))->toBe('')
        ->and(card_look_for('product-loop', '', ['tiles']))->toBe('')
        ->and(card_look_for('product-loop', 'panel', ['tiles']))->toBe('');
});

it('reads a site file of block => look and ignores anything else', function () {
    $file = sys_get_temp_dir() . '/pkde-cards-' . bin2hex(random_bytes(4)) . '.json';
    file_put_contents($file, json_encode(['product-loop' => 'tiles', 'latest-posts' => 'panel', 'bad' => ['x'], 5 => 'y']));
    expect(card_defaults_read($file))->toBe(['product-loop' => 'tiles', 'latest-posts' => 'panel']);
    file_put_contents($file, 'not json');
    expect(card_defaults_read($file))->toBe([])
        ->and(card_defaults_read(''))->toBe([])
        ->and(card_defaults_read($file . '.missing'))->toBe([]);
    unlink($file);
});
