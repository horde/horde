<?php
/**
 * Edit browsing
 *
 * Copyright 2006 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: edit.php 372 2008-02-29 12:05:00Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */

/* application include */
define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';
require_once NEWS_BASE . '/lib/Forms/Search.php';

/* redirect if not an admin */
$allowed_cats = $news_cat->getAllowed(PERMS_DELETE);
if (empty($allowed_cats)) {
    $notification->push(_("You have not editor permission on any category."));
    header('Location: ' . Horde::applicationUrl('add.php'));
    exit;
}

$id = Util::getFormData('id', 0);
$page = Util::getFormData('page', 0);
$browse_url = Horde::applicationUrl('edit.php');
$news_url = Horde::applicationUrl('news.php');
$edit_url = Horde::applicationUrl('add.php');
$read_url = Horde::applicationUrl('reads.php');
$has_comments = $registry->hasMethod('forums/doComments');
$actionID = Util::getFormData('actionID');

if (!empty($actionID) && $id > 0) {
    /* save as future version */
    $version = $news->db->getOne('SELECT MAX(version) FROM ' . $news->prefix . '_versions WHERE id=?', array($id));
    $result  = $news->writedb->query('INSERT INTO ' . $news->prefix . '_versions (id, version, action, created, user_uid) VALUES (?,?,?,NOW(),?)',
                                array($id, $version  + 1, $actionID, Auth::getAuth()));
}

switch ($actionID) {
case 'delete_attachments';

    $news->writedb->query('DELETE FROM ' . $news->prefix . '_attachment WHERE id = ?', array($id));
    $news->writedb->query('UPDATE ' . $news->prefix . ' SET attachments = ? WHERE id = ?', array(0, $id));

    $notification->push(sprintf(_("News %s: %s"), $id, _("Attachment deleted")), 'horde.success');
    header('Location: ' . $browse_url);
    exit;

break;
case 'deactivate';

    $news->writedb->query('UPDATE ' . $news->prefix . ' SET status = ? WHERE id = ?', array(News::UNCONFIRMED, $id));
    $notification->push(sprintf(_("News %s: %s"), $id, _("deactivated")), 'horde.success');
    header('Location: ' . $browse_url);
    exit;

break;
case 'activate';

    $news->writedb->query('UPDATE ' . $news->prefix . ' SET status = ? WHERE id = ?', array(News::CONFIRMED, $id));
    $notification->push(sprintf(_("News %s: %s"), $id, _("activated")), 'horde.success');
    header('Location: ' . $browse_url);
    exit;

break;
case 'lock';

    $news->writedb->query('UPDATE ' . $news->prefix . ' SET status = ? WHERE id = ?', array(News::LOCKED, $id));
    $notification->push(sprintf(_("News %s: %s"), $id, _("locked")), 'horde.success');
    header('Location: ' . $browse_url);
    exit;

break;
case 'unlock';

    $news->writedb->query('UPDATE ' . $news->prefix . ' SET status = ? WHERE id = ?', array(News::UNCONFIRMED, $id));
    $notification->push(sprintf(_("News %s: %s"), $id, _("unlocked")), 'horde.success');
    header('Location: ' . $browse_url);
    exit;

break;
case 'renew';

    $version = Util::getFormData('version');

    $version_data = $news->db->getRow('SELECT content FROM ' . $news->prefix . '_versions WHERE id = ? AND version = ?',
                                      array($id, $version), DB_FETCHMODE_ASSOC);

    $version_data['content'] = unserialize($version_data['content']);
    $news->writedb->query('DELETE FROM ' . $news->prefix . '_body WHERE id = ?', array($id));

    $new_version = array();
    $sql = 'INSERT INTO ' . $news->prefix . '_body (id,lang,title,abbreviation,content) VALUES (?,?,?,?,?)';

    foreach ($version_data['content'] as $lang => $values) {
        $new_version[$lang] = $values;
        $data = array($id,
                      $lang,
                      $values['title'],
                      substr(strip_tags($values['content']), 0, $conf['preview']['list_content']),
                      $values['content']);
        $news->writedb->query($sql, $data);
    }

    /* save as future version */
    $version = $news->db->getOne('SELECT MAX(version) FROM ' . $news->prefix . '_versions WHERE id = ?', array($id)) + 1;
    $result  = $news->writedb->query('INSERT INTO ' . $news->prefix . '_versions (id, version, created, user_uid, content) VALUES (?,?,NOW(),?,?)',
                                array($id, $version, Auth::getAuth(), serialize($new_version)));

    $notification->push(sprintf(_("News %s: %s"), $id, _("renewed")), 'horde.success');
    header('Location: ' . $browse_url);
    exit;

break;
}

$title = _("Edit");
$vars = Variables::getDefaultVariables();
$form = new News_Search($vars);
$form->getInfo(null, $info);

/* prepare query */
$binds = $news->buildQuery(PERMS_DELETE, $info);
$sql = 'SELECT n.id, n.sortorder, n.category1, n.category2, n.source, n.status, n.editor, n.publish, ' .
       'n.user, n.comments, n.unpublish, n.picture, n.chars, n.view_count, n.attachments, l.title, n.selling '
       . $binds[0];

if (!isset($info['sort_by'])) {
    $info['sort_by'] = 'n.publish';
}
if (!isset($info['sort_dir'])) {
    $info['sort_dir'] = 'DESC';
}

$sql .= ' ORDER BY ' . $info['sort_by'] . ' ' . $info['sort_dir'];

// Count rows
$count = $news->countNews($info, PERMS_DELETE);
if ($count instanceof PEAR_Error) {
    echo $count->getMessage() . ': ' . $count->getDebugInfo();
    exit;
}

// Select rows
$page = Util::getGet('news_page', 0);
$per_page = $prefs->getValue('per_page');
$sql = $news->db->modifyLimitQuery($sql, $page*$per_page, $per_page);
$rows = $news->db->getAll($sql, $binds[1], DB_FETCHMODE_ASSOC);
if ($rows instanceof PEAR_Error) {
    echo $rows->getMessage() . ': ' . $rows->getDebugInfo();
    exit;
}

// Get pager
$pager = News_Search::getPager($binds[1], $count, $browse_url);

/* Output */
Horde::addScriptFile('tables.js', 'horde', true);
Horde::addScriptFile('popup.js', 'horde', true);

require_once NEWS_TEMPLATES . '/common-header.inc';
require_once NEWS_TEMPLATES . '/menu.inc';
require_once NEWS_TEMPLATES . '/edit/header.inc';

$img_dir = $registry->getImageDir('horde');
foreach ($rows as $row) {
    require NEWS_TEMPLATES . '/edit/row.php';
    if ($row['id'] == $id ) {
        require NEWS_TEMPLATES . '/edit/info.php';
    }
}

require NEWS_TEMPLATES . '/edit/footer.inc';

$form->renderActive(null, null, null, 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
