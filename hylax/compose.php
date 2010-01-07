<?php
/**
 * The Hylax script to compose a new fax.
 *
 * $Horde: incubator/hylax/compose.php,v 1.12 2009/06/10 17:33:26 slusarz Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Joel Vandal <joel@scopserv.com>
 */

@define('HYLAX_BASE', dirname(__FILE__));
require_once HYLAX_BASE . '/lib/base.php';
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Form/Action.php';
require_once 'Horde/Template.php';

/* Load Cover Page templates */
require HYLAX_BASE . '/config/covers.php';

/* Get Cover Page template name */
$covers = array();
foreach ($_covers as $id => $cover) {
    $covers[$id] = $cover['name'];
}

$tpl = Horde_Util::getFormData('template', 'default');
if (empty($_covers[$tpl])) {
    Horde::fatal(_("The requested Cover Page does not exist."), __FILE__, __LINE__);
}

/* Load Form Actions */
$action = Horde_Form_Action::factory('submit');

/* Create Form */
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, _("Compose a new Fax"), 'compose');
$form->setButtons(_("Send"));
$form->appendButtons(_("Preview"));

/* Cover Page section */
$form->addVariable(_("Cover Page"), 'fromhdr', 'header', false);
$form->addVariable(_("Template"), 'template', 'enum', true, false, null, array($covers));
$form->addVariable(_("Fax Number"), 'faxnum', 'text', true);
$form->addVariable(_("Name"), 'name', 'text', false);
$form->addVariable(_("Company"), 'company', 'text', false);
$form->addVariable(_("Subject"), 'subject', 'text', false, false, null, array(false, 60));
$form->addVariable(_("Comment"), 'comment', 'longtext', false, false, null, array(4, 80));

/* Set up template. */
$template = new Horde_Template();
$template->set('form', '');
$template->set('menu', Hylax::getMenu('string'));
$template->set('notify', Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require HYLAX_TEMPLATES . '/common-header.inc';
echo $template->fetch(HYLAX_TEMPLATES . '/compose/compose.html');

$renderer = new Horde_Form_Renderer();
$form->renderActive($renderer, $vars, Horde::selfURL(), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
