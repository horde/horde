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
$url = Horde_Util::getFormData('url');
$folder = strtolower(Horde_Util::getFormData('folder'));
$path = Horde_Util::getFormData('path');
$base_folders = Hylax::getBaseFolders();

if (Horde_Util::getFormData('action') == 'download') {
    $filename = sprintf('fax%05d.pdf', $fax_id);
    $browser->downloadHeaders($filename);
    Hylax::getPDF($fax_id);
    exit;
}

$fax = $hylax->storage->getFax($fax_id);
if (is_a($fax, 'PEAR_Error')) {
    $notification->push(sprintf(_("Could not open fax ID \"%s\". %s"), $fax_id, $fax->getMessage()), 'horde.error');
    if (empty($url)) {
        $url = Horde::applicationUrl('folder.php', true);
    } else {
        $url = new Horde_Url($url);
    }
    $url->redirect();
}

$title = _("View Fax");

/* Get the preview pages. */
$pages = Hylax::getPages($fax_id, $fax['fax_pages']);

/* Set up template. */
$template = $injector->createInstance('Horde_Template');
$template->set('form', '');
$template->set('pages', $pages);
$template->set('menu', Hylax::getMenu('string'));

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require HYLAX_TEMPLATES . '/common-header.inc';
echo $template->fetch(HYLAX_TEMPLATES . '/fax/fax.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
