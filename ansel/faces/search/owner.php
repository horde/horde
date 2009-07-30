<?php
/**
 * Process an single image (to be called by ajax)
 *
 * $Horde: ansel/faces/search/owner.php,v 1.6 2009/07/08 18:28:41 slusarz Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once 'tabs.php';
require_once 'Horde/UI/Pager.php';

$page = Horde_Util::getFormData('page', 0);
$perpage = $prefs->getValue('facesperpage');
$owner = Horde_Util::getGet('owner', Horde_Auth::getAuth());
if ($owner == Horde_Auth::getAuth()) {
    $title = _("From my galleries");
} else {
    $title = sprintf(_("From galleries of %s"));
}

$count = $faces->countOwnerFaces($owner);
if (is_a($count, 'PEAR_Error')) {
    $notification->push($count);
    $results = array();
    $count = 0;
} else {
    $results = $faces->ownerFaces($owner, $page * $perpage, $perpage);
}

$vars = Horde_Variables::getDefaultVariables();
$pager = new Horde_UI_Pager(
    'page', $vars,
    array('num' => $count,
            'url' => 'faces/search/owner.php',
            'perpage' => $perpage));
$pager->preserve('owner', $owner);

require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';

include ANSEL_TEMPLATES . '/faces/faces.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';