<?php
/**
 * The Hylax script to show a summary view.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$hylax = Horde_Registry::appInit('hylax');

$fmt_inbox = array();
$inbox = $hylax->gateway->getFolder('inbox');
foreach ($inbox as $item) {
    $fmt_inbox[] = array('owner' => $item[2]);
}

$fmt_outbox = array();
$outbox = $hylax->gateway->getFolder('outbox');
foreach ($outbox as $item) {
    $fmt_outbox[] = array(//'time' => $item
                          'owner' => $item[2],
                          );
}

/* Set up actions. */
$template = $injector->createInstance('Horde_Template');
$template->set('in_faxes', $hylax->gateway->numFaxesIn());
$template->set('out_faxes', $hylax->gateway->numFaxesOut());
$template->set('inbox', $fmt_inbox, true);
$template->set('outbox', $fmt_outbox, true);
$template->set('menu', Hylax::getMenu('string'));

Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$template->set('notify', Horde::endBuffer());

require $registry->get('templates', 'horde') . '/common-header.inc';
echo $template->fetch(HYLAX_TEMPLATES . '/summary/summary.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
