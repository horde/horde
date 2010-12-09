<?php
/**
 * The Hylax script to show a fax view.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$hylax = Horde_Registry::appInit('hylax');

$fax_id = Horde_Util::getFormData('fax_id');
$folder = strtolower(Horde_Util::getFormData('folder'));
$path = Horde_Util::getFormData('path');
$base_folders = Hylax::getBaseFolders();

$vars = Horde_Variables::getDefaultVariables();
$fax_id = $vars->get('fax_id');
$url = $vars->get('url', 'folder.php');

$fax = $hylax->storage->getFax($fax_id);
if (is_a($fax, 'PEAR_Error')) {
    $notification->push(sprintf(_("Could not open fax ID \"%s\". %s"), $fax_id, $fax->getMessage()), 'horde.error');
    Horde::url($url, true)->redirect();
} elseif (!empty($fax['fax_number'])) {
    $notification->push(sprintf(_("Fax ID \"%s\" already has a fax number set."), $fax_id), 'horde.error');
    Horde::url($url, true)->redirect();
}

$title = _("Send Fax");

/* Set up the form. */
$form = new Horde_Form($vars, $title);
$form->setButtons(_("Send"), true);
$form->addHidden('', 'url', 'text', false);
$form->addHidden('', 'fax_id', 'int', false);
$form->addVariable(_("Fax destination"), 'fax_number', 'text', true, false, null, array('/^\d+$/'));

if ($form->validate($vars)) {
    $form->getInfo($vars, $info);
    $send = $hylax->storage->send($info['fax_id'], $info['fax_number']);
    if (is_a($send, 'PEAR_Error')) {
        $notification->push(sprintf(_("Could not send fax ID \"%s\". %s"), $info['fax_id'], $send->getMessage()), 'horde.error');
    } else {
        $notification->push(sprintf(_("Fax ID \"%s\" submitted successfully."), $info['fax_id']), 'horde.success');
    }
    Horde::url($url, true)->redirect();
}

/* Get the preview pages. */
$pages = Hylax::getPages($fax_id, $fax['fax_pages']);

/* Render the form. */
require_once 'Horde/Form/Renderer.php';
$renderer = new Horde_Form_Renderer();

Horde::startBuffer();
$form->renderActive($renderer, $vars, 'send.php', 'post');
$send_form = Horde::endBuffer();

/* Set up template. */
$template = $injector->createInstance('Horde_Template');
$template->set('form', $send_form);
$template->set('pages', $pages);
$template->set('menu', $menu->getMenu());

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(HYLAX_TEMPLATES . '/fax/fax.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
