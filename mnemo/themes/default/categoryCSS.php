<?php
/**
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('nag');

header('Content-Type: text/css');

$cManager = new Horde_Prefs_CategoryManager();

$colors = $cManager->colors();
$fgColors = $cManager->fgColors();
foreach ($colors as $category => $color) {
    if ($category == '_unfiled_' || $category == '_default_') {
        continue;
    }

    $class = '.category' . md5($category);

    echo "$class, .linedRow td$class, .overdue td$class, .closed td$class { "
        . 'color: ' . (isset($fgColors[$category]) ? $fgColors[$category] : $fgColors['_default_']) . '; '
        . 'background: ' . $color . '; '
        . "padding: 0 4px; }\n";

    $hex = str_replace('#', '', $color);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    echo "div.mnemo-stickies ul li a$class { color: black; "
        . "background: rgba($r, $g, $b, 0.5); }";
}
