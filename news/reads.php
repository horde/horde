<?php
/**
 * News reads
 *
 * Copyright 2006 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: reads.php 183 2008-01-06 17:39:50Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */

define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';

if (!Auth::isAuthenticated()) {
    Horde::authenticationFailureRedirect();
}

$id = Util::getFormData('id', 0);
$actionID = Util::getFormData('actionID', false);
$url = Horde::applicationUrl('reads.php');

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

Horde::addScriptFile('tables.js', 'horde', true);
Horde::includeScriptFiles();
echo Horde::stylesheetLink('news');

// require_once NEWS_TEMPLATES . '/common-header.inc';
require_once NEWS_TEMPLATES . '/reads/header.inc';

foreach ($result as $row) {
    require NEWS_TEMPLATES . '/reads/row.inc';
}

// require_once $registry->get('templates', 'horde') . '/common-footer.inc';
