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

$page = Horde_Util::getFormData('page', 0);
$perpage = $prefs->getValue('facesperpage');
$owner = Horde_Util::getGet('owner', $GLOBALS['registry']->getAuth());
if (!$owner) {
    $title = _("From system galleries");
} elseif ($owner == $GLOBALS['registry']->getAuth()) {
    $title = _("From my galleries");
} else {
    $title = sprintf(_("From galleries of %s"), $owner);
}

try {
    $count = $faces->countOwnerFaces($owner);
    $results = $faces->ownerFaces($owner, $page * $perpage, $perpage);
} catch (Ansel_Exception $e) {
    $notification->push($e->getMessage(), 'horde.err');
    $results = array();
    $count = 0;
}

$vars = Horde_Variables::getDefaultVariables();
$pager = new Horde_Core_Ui_Pager(
    'page',
    $vars,
    array(
        'num' => $count,
        'url' => 'faces/search/owner.php',
        'perpage' => $perpage
    )
);
$pager->preserve('owner', $owner);
require ANSEL_TEMPLATES . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
include ANSEL_TEMPLATES . '/faces/faces.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
