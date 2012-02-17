<?php
/**
 * Gollem quota script.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('gollem');

/* Is this a popup window? */
$isPopup = $browser->hasFeature('javascript');

/* Set up the template object. */
$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);
if ($isPopup) {
    $template->set('closebutton', _("Close"));
    Horde::addInlineScript(array(
        '$("closebutton").observe("click", function() { window.close(); })'
    ), 'dom');
}

/* Get the quota information. */
$template->set('noquota', true, true);
$template->set('quotaerror', false, true);
$template->set('quotadisplay', false, true);
$template->set('quotagraph', false, true);
if (Gollem::$backend['quota_val'] > -1) {
    $template->set('noquota', false, true);
    try {
        $quota_info = $injector->getInstance('Gollem_Vfs')->getQuota();
        $usage = $quota_info['usage'] / (1024 * 1024.0);
        $limit = $quota_info['limit'] / (1024 * 1024.0);

        $percent = ($usage * 100) / $limit;
        if ($percent >= 90) {
            $template->set('quotastyle', '<div style="color:red">');
        } else {
            $template->set('quotastyle', '<div>');
        }
        $template->set('quotadisplay', sprintf(_("%.2fMB / %.2fMB  (%.2f%%)"), $usage, $limit, $percent), true);
    } catch (Horde_Vfs_Exception $e) {
        $template->set('quotaerror', true, true);
        $template->set('quotaerrormsg', $e->getMessage());
    }
}

$title = _("Quota Display");
require $registry->get('templates', 'horde') . '/common-header.inc';
require GOLLEM_TEMPLATES . '/javascript_defs.php';
if (!$isPopup) {
    Gollem::menu();
    Gollem::status();
}
echo $template->fetch(GOLLEM_TEMPLATES . '/quota/quota.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
