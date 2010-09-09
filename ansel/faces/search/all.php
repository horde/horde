<?php
/**
 * Process an single image (to be called by ajax)
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
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

require ANSEL_TEMPLATES . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
include ANSEL_TEMPLATES . '/faces/faces.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
