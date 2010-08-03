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

$vars = &Horde_Variables::getDefaultVariables();

/* Create upload form */
$form = new Horde_Form($vars, _("Upload new Translation"), 'upload');
$form->setButtons(_("Upload"));
$form->addVariable(_("Module"), 'module', 'enum', true, false, null, array(Babel::listApps(true), true));
$form->addVariable(_("Translations File (.PO)"), 'po_file', 'file', true, false);
$form->addVariable('', '', 'spacer', true);

/* Validate form if submitted */
if (Horde_Util::getFormData('submitbutton') == _("Upload")) {
    if ($form->validate($vars, false)) {
	$form->getInfo($vars, $form_values);
	
	$po_module = @$form_values['module'];
	if (empty($po_module)) {
	    $notification->push(_("Please select module of translations PO file!"), 'horde.error');
	} else {
	    $po_file_path = @$form_values['po_file']['file'];
	    $po_file_name = @$form_values['po_file']['name'];
	    $po_file_size = @$form_values['po_file']['size'];
	    if (empty($po_file_path) || substr($po_file_name, -3) != '.po' || $po_file_size <= 0) {
		$notification->push(_("Invalid Translations file. Please submit a valid PO file!"), 'horde.error');
	    } else {
		if ($po_module == 'horde') {
		    $mod = '';
		} else {
		    $mod = $po_module;
		}
		
		$notification->push(sprintf(_("Upload successful for %s (%s)"), $po_module, $lang), 'horde.success');
		$cmd = "cp $po_file_path " . HORDE_BASE . "/$mod/po/$lang.po";
		system($cmd);
		
		// Redirect to page URL
		Horde::applicationUrl('upload.php')->redirect();
	    }
	}
    }
}

/* Render upload page */
require BABEL_TEMPLATES . '/common-header.inc';
echo $template->fetch(BABEL_TEMPLATES . '/layout.html');

$renderer_params = array();
$renderer = new Horde_Form_Renderer($renderer_params);
$renderer->setAttrColumnWidth('20%');

$form->renderActive($renderer, $vars, Horde::selfURL(), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
