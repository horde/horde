<?php
/**
 * News
 *
 * $Id: news.php 1190 2009-01-21 16:10:50Z duck $
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

$id = Horde_Util::getFormData('id');
$row = $news->get($id);

// check if the news exists
if ($row instanceof PEAR_Error) {
    $notification->push($row);
    Horde::applicationUrl('index.php')->redirect();
}

// check if the news exists
if (($version = Horde_Util::getFormData('version')) !== null) {
    $sql = 'SELECT created, user_uid, content FROM ' . $news->prefix . '_versions WHERE id = ? AND version = ?';
    $version_data = $news->db->getRow($sql, array($id, $version), DB_FETCHMODE_ASSOC);
    if (empty($version_data)) {
        $notification->push(_("There requested version don't exist."), 'horde.error');
        exit;
    } else {
        $version_data['content'] = unserialize($version_data['content']);
        $row['content'] = $version_data['content'][NLS::select()]['content'];
        $row['title'] = $version_data['content'][NLS::select()]['title'] .
                        ' (v.' . $version . _(" by ") . $version_data['user_uid'] .
                        ' @ ' . News::dateFormat($version_data['created'])  . ')';
    }
} else {
    $news->logView($id);
}

$title = $row['title'];
$template_path = News::getTemplatePath($row['category1'], 'news');
$browse_url = Horde::applicationUrl('browse.php');

require_once NEWS_TEMPLATES . '/common-header.inc';
require_once NEWS_TEMPLATES . '/menu.inc';

require $template_path . 'news.php';

require_once $registry->get('templates', 'horde') . '/common-footer.inc';
