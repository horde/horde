<?php
/**
 * Gollem quota script.
 *
 * Copyright 2005-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('gollem');

/* Is this a popup window? */
$isPopup = $browser->hasFeature('javascript');

/* Set up the template object. */
$template = $injector->createInstance('Horde_View');
if ($isPopup) {
    $template->closebutton = _("Close");
    $page_output->topbar = $page_output->sidebar = false;
    $page_output->addInlineScript(array(
        '$("closebutton").observe("click", function() { window.close(); })'
    ), true);
}

/* Get the quota information. */
$template->noquota = true;
$template->quotaerror = false;
$template->quotadisplay = false;
$template->quotagraph = false;
if (!empty(Gollem::$backend['quota'])) {
    $template->noquota = false;
    try {
        $quota_info = $injector->getInstance('Gollem_Vfs')->getQuota();
        $usage = $quota_info['usage'] / (1024 * 1024.0);
        $limit = $quota_info['limit'] / (1024 * 1024.0);

        $percent = ($usage * 100) / $limit;
        if ($percent >= 90) {
            $template->quotastyle = '<div style="color:red">';
        } else {
            $template->quotastyle = '<div>';
        }
        $template->quotadisplay = sprintf(_("%.2fMB / %.2fMB  (%.2f%%)"), $usage, $limit, $percent);
    } catch (Horde_Vfs_Exception $e) {
        $template->quotaerror = true;
        $template->quotaerrormsg = $e->getMessage();
    }
}

$page_output->header(array(
    'title' => _("Quota Display")
));
if (!$isPopup) {
    $notification->notify(array('listeners' => 'status'));
}
echo $template->render('quota');
$page_output->footer();
