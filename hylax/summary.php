<?php
/**
 * The Hylax script to show a summary view.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * $Horde: incubator/hylax/summary.php,v 1.6 2009/06/10 05:24:17 slusarz Exp $
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$hylax = new Hylax_Application(array('init' => true));

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
$template = new Horde_Template();
$template->set('in_faxes', $hylax->gateway->numFaxesIn());
$template->set('out_faxes', $hylax->gateway->numFaxesOut());
$template->set('inbox', $fmt_inbox, true);
$template->set('outbox', $fmt_outbox, true);
$template->set('menu', Hylax::getMenu('string'));
$template->set('notify', Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));

require HYLAX_TEMPLATES . '/common-header.inc';
echo $template->fetch(HYLAX_TEMPLATES . '/summary/summary.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
