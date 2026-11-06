<?php

/**
 * Where the star rating sits in the kit's product card (owner via the manager, MGR-77): by default just above the add-to-cart button, under the title and
 * the price, in DOM order, so the reading order and the visual order are the same (a site used to move it with CSS `order`, which reorders only what
 * the eye sees). `pkde_kit/card_rating_position` (string) changes it:
 *
 *   'above_button'  after the title, the price and the short description, directly above the action row (default)
 *   'below_title'   after the title, before the price
 *   'above_title'   before the title (the card's older look)
 *
 * A site that copies the card prints its rating where it wants and needs no option.
 */

namespace App;

/** The positions the card knows, the default first. */
const CARD_RATING_POSITIONS = ['above_button', 'below_title', 'above_title'];

/** A position from a filter, or the default for anything else (pure). */
function normalize_card_rating_position($position): string
{
    return in_array($position, CARD_RATING_POSITIONS, true) ? $position : CARD_RATING_POSITIONS[0];
}

/** The position the card uses (`pkde_kit/card_rating_position`). */
function card_rating_position(): string
{
    return normalize_card_rating_position(function_exists('apply_filters') ? apply_filters('pkde_kit/card_rating_position', CARD_RATING_POSITIONS[0]) : CARD_RATING_POSITIONS[0]);
}
