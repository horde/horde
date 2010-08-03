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
$url = Horde_Util::getFormData('url', 'folder.php');
$print = Hylax::printFax($fax_id);
if (is_a($print, 'PEAR_Error')) {
    $notification->push(sprintf(_("Could not print fax ID \"%s\". %s"), $fax_id, $print->getMessage()), 'horde.error');
} else {
    $notification->push(sprintf(_("Printed fax ID \"%s\"."), $fax_id), 'horde.success');
}

/* Redirect back. */
Horde::applicationUrl($url, true)->redirect();
