<?php
/**
 * Image search
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see /var/www/www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
require_once 'tabs.php';

$page = Horde_Util::getFormData('page', 0);
$perpage = $prefs->getValue('facesperpage');

if (($face_id = Horde_Util::getGet('face_id')) !== null) {
    try {
        $face = $faces->getFaceById($face_id, true);
        $signature = $face['face_signature'];
        $results = $faces->getSignatureMatches($signature, $face_id, $perpage * $page, $perpage);
    } catch (Ansel_Exception $e) {
        $notification->push($e->getMessage());
        Horde::url('faces/search/image.php')->redirect();
    }
} else {
    $tmp = Horde::getTempDir();
    $path = $tmp . '/search_face_' . $registry->getAuth() . '.sig';
    if (file_exists($path) !== true) {
        $notification->push(_("You must upload the search photo first"));
        Horde::url('faces/search/image.php')->redirect();
    }
    $signature = file_get_contents($path);
    try {
        $results = $faces->getSignatureMatches($signature, 0, $perpage * $page, $perpage);
    } catch (Ansel_Exception $e) {
        $notification->push($e->getMessage());
        $results = array();
    }
}

$title = _("Photo search");
$vars = Horde_Variables::getDefaultVariables();
$pager = new Horde_Core_Ui_Pager(
    'page',
    $vars,
    array(
        'num' => count($results),
        'url' => 'faces/search/image_search.php',
        'perpage' => $perpage
    )
);

require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
require ANSEL_TEMPLATES . '/faces/search.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
