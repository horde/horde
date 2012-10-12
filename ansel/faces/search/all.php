<?php
/**
 * Process an single image (to be called by ajax)
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 */
require_once 'tabs.php';

$title = _("All faces");
$page = Horde_Util::getFormData('page', 0);
$perpage = $prefs->getValue('facesperpage');

try {
    $count = $faces->countAllFaces();
    $results = $faces->allFaces($page * $perpage, $perpage);
} catch (Ansel_Exception $e) {
    $notification->push($count->getDebugInfo());
    $count = 0;
    $results = array();
}
$vars = Horde_Variables::getDefaultVariables();
$pager = new Horde_Core_Ui_Pager(
    'page', $vars,
    array(
        'num' => $count,
        'url' => 'faces/search/all.php',
        'perpage' => $perpage
    )
);

$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
include ANSEL_TEMPLATES . '/faces/faces.inc';
$page_output->footer();
