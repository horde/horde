<?php
/**
 * Gollem quota script.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
new Gollem_Application(array('init' => true));

/* Is this a popup window? */
$isPopup = $browser->hasFeature('javascript');

$title = _("Quota Display");
require GOLLEM_TEMPLATES . '/common-header.inc';
if (!$isPopup) {
    Gollem::menu();
    Gollem::status();
}

/* Set up the template object. */
$template = new Horde_Template();
$template->setOption('gettext', true);
$template->set('hasjs', false, true);
if ($isPopup) {
    $template->set('hasjs', true, true);
    $template->set('closebutton', _("Close"));
}

/* Get the quota information. */
$template->set('noquota', true, true);
$template->set('quotaerror', false, true);
$template->set('quotadisplay', false, true);
$template->set('quotagraph', false, true);
if ($GLOBALS['gollem_be']['quota_val'] > -1) {
    $template->set('noquota', false, true);
    $quota_info = $GLOBALS['gollem_vfs']->getQuota();
    if (is_a($quota_info, 'PEAR_Error')) {
        $template->set('quotaerror', true, true);
        $template->set('quotaerrormsg', $quota_info->getMessage());
    } else {
        $usage = $quota_info['usage'] / (1024 * 1024.0);
        $limit = $quota_info['limit'] / (1024 * 1024.0);

        $percent = ($usage * 100) / $limit;
        if ($percent >= 90) {
            $template->set('quotastyle', '<div style="color:red">');
        } else {
            $template->set('quotastyle', '<div>');
        }
        $template->set('quotadisplay', sprintf(_("%.2fMB / %.2fMB  (%.2f%%)"), $usage, $limit, $percent), true);
    }
}

echo $template->fetch(GOLLEM_TEMPLATES . '/quota/quota.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
