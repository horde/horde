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

/* Search from */
$form = new Horde_Form($vars);
$form->addVariable(_("Face name to search"), 'face_name', 'text', true);
$form->setButtons(_("Search"));

$page = Horde_Util::getFormData('page', 0);
$perpage = $prefs->getValue('facesperpage');

$name = Horde_Util::getFormData('face_name');
if (!empty($name)) {
    $page = Horde_Util::getFormData('page', 0);
    $perpage = $prefs->getValue('faceperpage');
    $count = $faces->countSearchFaces($name);
    if ($count) {
        $results = $faces->searchFaces($name, $page * $perpage, $perpage);
    }
} else {
    $page = 0;
    $perpage = 0;
    $count = 0;
}

$vars = Horde_Variables::getDefaultVariables();
$pager = new Horde_Core_Ui_Pager(
    'page', $vars,
    array('num' => $count,
            'url' => 'faces/search/name.php',
            'perpage' => $perpage));

$page_output->header(array(
    'title' => _("Search by name")
));
$notification->notify(array('listeners' => 'status'));
include ANSEL_TEMPLATES . '/faces/faces.inc';
$page_output->footer();
