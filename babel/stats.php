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
$form = new Horde_Form($vars, _("View Statistics"), 'stats');

if (!$app) {
    $form->setButtons(_("View"));
    $form->addVariable(_("Module"), 'module', 'enum', true, false, null, array(Babel::listApps(true), true));
    $form->addVariable('', '', 'spacer', true);
    
    $renderer_params = array();
    $renderer = new Horde_Form_Renderer($renderer_params);
    $renderer->setAttrColumnWidth('20%');
    
    $form->renderActive($renderer, $vars, Horde::selfURL(), 'post');
} else {
    Translate_Display::header(_("Horde translation generator"));
    
    $dirs = Translate::search_applications();
    $apps = Translate::strip_horde($dirs);
    $apps[0] = 'horde';
    Translate_Display::info();
    
    foreach($dirs as $d => $dir) {
	$dir = realpath($dir);
	$pofile  = $dir . '/po/' . $lang . '.po';
	
	if (!@file_exists($pofile)) {
	    continue;
	}

	$_app = str_replace(realpath(HORDE_BASE), '', $dir);
	$_app = str_replace('/', '', $_app);
	if (empty($_app)) {
	    $_app = 'horde';
	}
	
	if ($app != 'ALL' && $app != $_app) {
	    continue;
	}

	if (!Babel::hasPermission("module:$_app")) {
	    continue;
	}
	
	Translate_Display::header($_app);
	
	$report = Translate::stats($_app);
	
	echo '<table width="100%" align="center" border="0" cellspacing="0" cellpadding="0">';
	echo '<tr class="control">';
	echo '<td class="control" style="border-bottom: 1px solid #999;"><b>' . _("Language") . '</b></td>';
	echo '<td width="5%"><b>' . _("Locale") . '</b></td>';
	echo '<td width="15%"><b>' . _("Status") . '</b></td>';
	echo '<td valign="bottom" style="width: 80px;"><b>' . _("Translated") . '</b></td>';
	echo '<td valign="bottom" style="width: 80px;"><b>' . _("Fuzzy") . '</b></td>';
	echo '<td valign="bottom" style="width: 80px;"><b>' . _("Untranslated") . '</b></td>';
	echo '<td valign="bottom" style="width: 80px;"><b>' . _("Obsolete") . '</b></td>';
	echo '</tr>';	
	
	$i = 0;
	$j = 0;
	$line = 0;
	$last_key = null;
	foreach ($report as $key => $value) {
	    
	    if (!Babel::hasPermission("language:$key")) {
		continue;
	    }

	    if ($key == $_SESSION['babel']['language']) {
		echo "\n<tr class=\"smallheader control\">";
	    } else {
		echo "\n<tr class=\"item" . ($i++ % 2) . "\">";
	    }
	    echo "\n\t<td>" . Horde_Nls::$config['languages'][$key] . "</td>";
	    echo "\n\t<td>" . Horde::link(Horde_Util::addParameter(Horde::url('view.php'), array('display_language' => $key, 'module' => $_app))) . $key . '</a>' . "</td>";
	    echo "\n\t<td>" . Translate_Display::create_bargraph($value[2], $value[0]) . "</td>";
	    echo "\n\t<td>" . $value[2] . "</td>";
	    echo "\n\t<td>" . $value [3] . "</td>";
	    echo "\n\t<td>" . $value[4] . "</td>";
	    echo "\n\t<td>" . $value[5] . "</td>";
	    echo "\t</tr>";
	    $last_key = $key;
	}
	
	echo '</table>';
	
	Translate_Display::info();
    }
    
    Babel::RB_close();
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
