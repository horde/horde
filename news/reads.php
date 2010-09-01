<?php
/**
 * News reads
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */

require_once dirname(__FILE__) . '/lib/base.php';

if (!$registry->isAuthenticated()) {
    $registry->authenticateFailure('news');
}

$id = Horde_Util::getFormData('id', 0);
$actionID = Horde_Util::getFormData('actionID', false);
$url = Horde::url('reads.php');

$sql = 'SELECT id, user, ip, readdate FROM ' . $news->prefix . '_user_reads WHERE ';
if ($actionID) {
    $title = $actionID;
    $result = $news->db->getAll($sql . 'user = ? ORDER BY readdate DESC', array($actionID), DB_FETCHMODE_ASSOC);
} else {
    $result = $news->db->getAll($sql . 'id = ? ORDER BY readdate DESC', array($id), DB_FETCHMODE_ASSOC);
    $title = sprintf(_("News %s"), $id);
}

if ($result instanceof PEAR_Error) {
    var_dump($result);
    exit;
}

Horde::addScriptFile('tables.js', 'horde');
Horde::includeScriptFiles();
Horde_Themes::includeStylesheetFiles();

// require_once NEWS_TEMPLATES . '/common-header.inc';
require_once NEWS_TEMPLATES . '/reads/header.inc';

foreach ($result as $row) {
    require NEWS_TEMPLATES . '/reads/row.inc';
}

// require_once $registry->get('templates', 'horde') . '/common-footer.inc';
