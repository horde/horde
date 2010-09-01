<?php
// Here we should tell the view using this helper to include the
// relevant CSS and JavaScript. Currently CSS is in the main Trean CSS
// file.
Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('star_rating.js', 'trean', true);

// Eventual class var.
define('_STAR_WIDTH', 25);

/**
 * Render a CSS-based star-rater.
 */
function star_rating_helper($bookmark)
{
    $edit = Horde::url('bookmark.php?b=' . $bookmark->id, true);
    return '
<ol class="star-rating" for="' . htmlspecialchars($edit) . '">
 <li class="current-rating" style="width:' . (_STAR_WIDTH * $bookmark->rating) . 'px">' . str_repeat('*', $bookmark->rating) . '</li>
 <li><a href="' . Horde_Util::addParameter($edit, 'r', 1) . '" title="' . _("1 star out of 5") . '" rating="1" class="one-star">1</a></li>
 <li><a href="' . Horde_Util::addParameter($edit, 'r', 2) . '" title="' . sprintf(_("%d stars out of 5"), 2) . '" rating="2" class="two-stars">2</a></li>
 <li><a href="' . Horde_Util::addParameter($edit, 'r', 3) . '" title="' . sprintf(_("%d stars out of 5"), 3) . '" rating="3" class="three-stars">3</a></li>
 <li><a href="' . Horde_Util::addParameter($edit, 'r', 4) . '" title="' . sprintf(_("%d stars out of 5"), 4) . '" rating="4" class="four-stars">4</a></li>
 <li><a href="' . Horde_Util::addParameter($edit, 'r', 5) . '" title="' . sprintf(_("%d stars out of 5"), 5) . '" rating="5" class="five-stars">5</a></li>
</ol>';
}

function static_star_rating_helper($bookmark)
{
    return '
<a class="static-rating" style="width:' . (_STAR_WIDTH * $bookmark->rating) . 'px">' . str_repeat('*', $bookmark->rating) . '</a>';
}
