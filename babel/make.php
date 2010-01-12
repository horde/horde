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
    /* Render the page. */
    Babel::RB_init();
}

require BABEL_TEMPLATES . '/common-header.inc';

if ($app) {
    Babel::RB_start(30);
}

echo $template->fetch(BABEL_TEMPLATES . '/layout.html');

$vars = &Horde_Variables::getDefaultVariables();

/* Create upload form */
$form = new Horde_Form($vars, _("Make Translation"), 'make');

if (!$app) {
    $form->setButtons(_("Make"));
    $form->addVariable(_("Module"), 'module', 'enum', true, false, null, array(Babel::listApps(true), true));
    $form->addVariable('', '', 'spacer', true);
    
    $renderer_params = array();
    $renderer = new Horde_Form_Renderer($renderer_params);
    $renderer->setAttrColumnWidth('20%');
    
    $form->renderActive($renderer, $vars, Horde::selfURL(), 'post');
} else {

    if ($app != 'ALL') {
	$module = $app;
    }
    
    Translate::sanity_check();
    
    /* Searching applications */
    Translate::check_binaries();
    
    Translate_Display::info(sprintf(_("Searching Horde applications in %s"), realpath(HORDE_BASE)));
    $dirs = Translate::search_applications();
        
    $apps = Translate::strip_horde($dirs);
    $apps[0] = 'horde';
    Translate_Display::info(_("Found applications:"));
    Translate_Display::info(wordwrap(implode(', ', $apps)), false);
    Translate_Display::info();
    
    Translate_Display::header(_("Generate Compendium ..."));
    Translate::compendium();
    Translate_Display::info();
    
    Translate::cleanup(true);
    Translate_Display::info();
    Translate::make();
    
    Babel::RB_close();
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
