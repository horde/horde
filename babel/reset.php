<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Joel Vandal <joel@scopserv.com>
 * @package Babel
 */

@define('BABEL_BASE', dirname(__FILE__)) ;
require_once BABEL_BASE . '/lib/base.php';

Babel::RB_init();

/* Render the page. */
require BABEL_TEMPLATES . '/common-header.inc';

Babel::RB_start(15);

echo $template->fetch(BABEL_TEMPLATES . '/layout.html');

Translate_Display::header(_("Horde translation generator"));

Translate_Display::info(sprintf('Searching Horde applications in %s', realpath(HORDE_BASE)));

Translate::check_binaries();

$dirs = Translate::search_applications();

$apps = Translate::strip_horde($dirs);
$apps[0] = 'horde';
Translate_Display::info(_("Found applications:"));
Translate_Display::info(wordwrap(implode(', ', $apps)), false);
Translate_Display::info();

Translate_Display::header(_("Reset PO files ..."));
foreach($dirs as $d => $dir) {
    $dir = realpath($dir);
    $po  = $dir . '/po/' . $lang . '.po';
    
    Translate_Display::info(_("Reset PO file on ") . $po);
    Babel::callHook('reset', $po);

}

Translate_Display::info();
Translate::cleanup(true);

Babel::RB_close();

require $registry->get('templates', 'horde') . '/common-footer.inc';
