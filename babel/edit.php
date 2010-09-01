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

$meta_params = array(
		     "Project-Id-Version" => @$_SESSION['babel']['language'],
		     "Report-Msgid-Bugs-To" => "support@scopserv.com",
		     "POT-Creation-Date" => "",
		     "PO-Revision-Date" => "",
		     "Last-Translator" => "",
		     "Language-Team" => "",
		     "MIME-Version" => "1.0",
		     "Content-Type" => "text/plain; charset=utf-8",
		     "Content-Transfer-Encoding" => "8bit",
		     "Plural-Forms" => "nplurals=2; plural=(n > 1);");


require_once dirname(__FILE__) . '/lib/base.php';
require_once BABEL_BASE . '/lib/Gettext/PO.php';

require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Form/Action.php';

$app = Horde_Util::getFormData('module');

$show = 'edit';
$vars = &Horde_Variables::getDefaultVariables();

if ($app) {
    $napp = ($app == 'horde') ? '' : $app;
    $pofile = HORDE_BASE . '/' . $napp . '/po/' . $_SESSION['babel']['language'] . '.po';
    $po = new File_Gettext_PO();
    $po->load($pofile);
}

/* Set up the template fields. */
$template->set('menu', Babel::getMenu()->render());

Horde::startBuffer();
$notification->notify(array('listeners' => 'status')));
$template->set('notify', Horde::endBuffer());

/* Create upload form */
$form = new Horde_Form($vars, _("Edit Translation"), $show);

/* Validate form if submitted */
if ($app && Horde_Util::getFormData('submitbutton') == _("Save")) {

    if ($form->validate($vars, false)) {
	$form->getInfo($vars, $form_values);

	foreach($meta_params as $k => $v) {
	    if ($val = Horde_Util::getFormData($k)) {
		$po->meta[$k] = $val;
	    }
	}

	$po->save($pofile);

	if (Horde_Util::getFormData('url') == 'view') {
	    Horde::url('view.php')->add('module' => $app)->redirect();
	}
    }
}

if (!$app) {
    $form->setButtons(_("Edit"));
    $form->addVariable(_("Module"), 'module', 'enum', true, false, null, array(Babel::listApps(), true));
    $form->addVariable('', '', 'spacer', true);
} else {

    $form->setButtons(_("Save"));
    $form->addHidden('', 'module', 'text', false);
    $vars->set('module', $app);

    $form->addHidden('', 'url', 'text', false);
    $vars->set('url', Horde_Util::getFormData('url'));

    foreach($meta_params as $k => $v) {
	$form->addVariable($k, $k, 'text', false, false);
	if (isset($po->meta[$k]) && !empty($po->meta[$k])) {
	    $vars->set($k, $po->meta[$k]);
	} elseif (!empty($v)) {
	    $vars->set($k, $v);
	}
    }
}

/* Render the page. */
require BABEL_TEMPLATES . '/common-header.inc';

echo $template->fetch(BABEL_TEMPLATES . '/layout.html');

$renderer_params = array();
$renderer = new Horde_Form_Renderer($renderer_params);
$renderer->setAttrColumnWidth('20%');

$form->renderActive($renderer, $vars, Horde::selfURL(), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
