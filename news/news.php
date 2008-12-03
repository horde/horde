<?php
/**
 * News
 *
 * Copyright 2006 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: news.php 229 2008-01-12 19:47:30Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */

define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';

$id = Util::getFormData('id');
$row = $news->get($id);

// check if the news eyists
if ($row instanceof PEAR_Error) {
    $notification->push($row->getMessage(), 'horde.error');
    header('Location: ' . Horde::applicationUrl('index.php'));
    exit;
}

// check if the news exists
if (($version = Util::getFormData('version')) !== null) {
    $sql = 'SELECT created, user_uid, content FROM ' . $news->prefix . '_versions WHERE id = ? AND version = ?';
    $version_data = $news->db->getRow($sql, array($id, $version), DB_FETCHMODE_ASSOC);
    if (empty($version_data)) {
        $notification->push(_("There requested version don't exist."), 'horde.error');
        exit;
    } else {
        $version_data['content'] = unserialize($version_data['content']);
        $row['content'] = $version_data['content'][NLS::select()]['content'];
        $row['title'] = $version_data['content'][NLS::select()]['title'] .
                        '<span class="small"> - v.' . $version . ' from ' . $version_data['user_uid'] .
                        ' @ ' . $version_data['created'] . ' </span>';
    }
} else {
    $news->logView($id);
}

$title = $row['title'];
$template_path = News::getTemplatePath($row['category1'], 'news');
$browse_url = Horde::applicationUrl('browse.php');
$news_url = Horde::applicationUrl('news.php', true);

Horde::addScriptFile('popup.js', 'horde', true);
require_once NEWS_TEMPLATES . '/common-header.inc';
require_once NEWS_TEMPLATES . '/menu.inc';

require $template_path . 'news.php';

require_once $registry->get('templates', 'horde') . '/common-footer.inc';

