<?php
/**
 * The Hylax script to compose a new fax.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Joel Vandal <joel@scopserv.com>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$hylax = Horde_Registry::appInit('hylax');

/* Load Cover Page templates */
require HYLAX_BASE . '/config/covers.php';

/* Get Cover Page template name */
$covers = array();
foreach ($_covers as $id => $cover) {
    $covers[$id] = $cover['name'];
}

$tpl = Horde_Util::getFormData('template', 'default');
if (empty($_covers[$tpl])) {
    throw new Horde_Exception(_("The requested Cover Page does not exist."));
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
$template = $injector->createInstance('Horde_Template');
$template->set('form', '');
$template->set('menu', Hylax::getMenu('string'));

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require HYLAX_TEMPLATES . '/common-header.inc';
echo $template->fetch(HYLAX_TEMPLATES . '/compose/compose.html');

$renderer = new Horde_Form_Renderer();
$form->renderActive($renderer, $vars, Horde::selfURL(), 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
