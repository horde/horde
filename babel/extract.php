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

set_time_limit(0);

@define('BABEL_BASE', dirname(__FILE__)) ;
require_once BABEL_BASE . '/lib/base.php';

if ($app) {
    Babel::RB_init();
}

/* Render the page. */
require BABEL_TEMPLATES . '/common-header.inc';

if ($app) {
    Babel::RB_start(300);
}

echo $template->fetch(BABEL_TEMPLATES . '/layout.html');

$vars = &Horde_Variables::getDefaultVariables();

/* Create upload form */
$form = new Horde_Form($vars, _("Extract Translation"), 'extract');

if (!$app) {
    $form->setButtons(_("Extract"));
    $form->addVariable(_("Module"), 'module', 'enum', true, false, null, array(Babel::listApps(true), true));
    $form->addVariable('', '', 'spacer', true);
    
    $renderer_params = array();
    $renderer = new Horde_Form_Renderer($renderer_params);
    $renderer->setAttrColumnWidth('20%');
    
    $form->renderActive($renderer, $vars, Horde::selfURL(), 'post');
} else {
    Translate_Display::header(_("Horde translation generator"));
    
    /* Sanity checks */
    if (!extension_loaded('gettext')) {
	Translate_Display::error(_("Gettext extension not found!"));
	footer();
    }
    
    Translate_Display::info(_("Loading libraries..."));
    $libs_found = true;
    
    foreach (array('Console_Getopt' => 'Console/Getopt.php',
		   'Console_Table'  => 'Console/Table.php',
		   'File_Find'      => 'File/Find.php')
	     as $class => $file) {
	@include_once $file;
	if (class_exists($class)) {
	    // Translate_Display::info("$class ...", false);
	} else {
	    Translate_Display::error(sprintf(_("%s not found."), $class));
	    $libs_found = false;
	}
    }
    
    if (!$libs_found) {
	Translate_Display::info();
	Translate_Display::info(_("Make sure that you have PEAR installed and in your include path."));
	Translate_Display::info('include_path: ' . ini_get('include_path'));
    }
    Translate_Display::info();
    
    /* Searching applications */
    Translate::check_binaries();
    
    Translate_Display::info(sprintf(_("Searching Horde applications in %s"), realpath(HORDE_BASE)));
    $dirs = Translate::search_applications();
    
    if ($app == 'ALL') {
	Translate_Display::info(_("Found directories:"), false);
	Translate_Display::info(implode("\n", $dirs), false);
    }
    Translate_Display::info();
    
    $apps = Translate::strip_horde($dirs);
    $apps[0] = 'horde';
    if ($app == 'ALL') {
	Translate_Display::info(_("Found applications:"));
	Translate_Display::info(wordwrap(implode(', ', $apps)), false);
	Translate_Display::info();
    }
    
    global $module;
    if ($app != 'ALL') {
	$module = $app;
    }
    
    Translate::init();
    Translate::cleanup();
    
    Translate_Display::header(_("Generate Compendium ..."));
    Translate::compendium();
    Translate_Display::info();
    
    Translate::xtract();
    Translate_Display::info();
    Translate::merge();
    
    Translate_Display::info();
    Translate_Display::header(_("Done!"));
    
    Babel::RB_close();
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
