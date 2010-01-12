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

// Define if we use Horde CVS or Custom Commit
$custom_commit = true;

/* Render the page. */
require BABEL_TEMPLATES . '/common-header.inc';
echo $template->fetch(BABEL_TEMPLATES . '/layout.html');

Translate_Display::header(_("Horde translation generator"));

/* Do sanity check */
Translate::sanity_check();

/* Searching applications */
Translate::check_binaries();

Translate_Display::info(sprintf('Searching Horde applications in %s', realpath(HORDE_BASE)));
$dirs = Translate::search_applications();

$apps = Translate::strip_horde($dirs);
$apps[0] = 'horde';
Translate_Display::info(_("Found applications:"));
Translate_Display::info(wordwrap(implode(', ', $apps)), false);
Translate_Display::info();

// Check if we must execute Custom commit or Horde CVS Commit (Developer)
if ($custom_commit) {
    Translate_Display::header(_("Commit PO files ..."));
    foreach($dirs as $d => $dir) {
	$dir = realpath($dir);
	$po  = $dir . '/po/' . $lang . '.po';
	
	if (@file_exists($po)) {
	    Translate_Display::info(_("Commit") . " $po ($lang)");
	    Babel::callHook('commit', array($po, $lang));
	}
	
    }
} else {
    Translate::commit();
}


Translate_Display::info();

require $registry->get('templates', 'horde') . '/common-footer.inc';
