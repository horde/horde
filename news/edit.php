<?php
/**
 * Edit browsing
 *
 * $Id: edit.php 1188 2009-01-21 10:33:56Z duck $
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

// redirect if not an admin
$allowed_cats = $news_cat->getAllowed(Horde_Perms::DELETE);
if (empty($allowed_cats)) {
    $notification->push(_("You have not editor permission on any category."));
    Horde::url('add.php')->redirect();
}

$id = (int)Horde_Util::getFormData('id', 0);
$page = (int)Horde_Util::getFormData('page', 0);
$browse_url = Horde_Util::addParameter(Horde::url('edit.php'), array('page' => $page, 'id' => $id), null, false);
$edit_url = Horde::url('add.php');
$read_url = Horde::url('reads.php');
$has_comments = $registry->hasMethod('forums/doComments');
$actionID = Horde_Util::getFormData('actionID');

// save as future version
if (!empty($actionID) && $id > 0) {
    $version = $news->db->getOne('SELECT MAX(version) FROM ' . $news->prefix . '_versions WHERE id = ?', array($id));
    $result  = $news->write_db->query('INSERT INTO ' . $news->prefix . '_versions (id, version, action, created, user_uid) VALUES (?,?,?,NOW(),?)',
                                        array($id, $version + 1, $actionID, $GLOBALS['registry']->getAuth()));
}

if ($id) {
    $article = $news->get($id);
}

switch ($actionID) {
case 'deletepicture';

    $result = News::deleteImage($id);
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    }

    $result = $news->write_db->query('UPDATE ' . $news->prefix . ' SET picture = ? WHERE id = ?', array(0, $id));
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    } else {
        $notification->push(sprintf(_("News \"%s\" (%s): %s"), $article['title'], $id, _("picture deleted")), 'horde.success');
    }

    $browse_url->redirect();

break;

case 'deactivate';

    $result = $news->write_db->query('UPDATE ' . $news->prefix . ' SET status = ? WHERE id = ?', array(News::UNCONFIRMED, $id));
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    }

    $notification->push(sprintf(_("News \"%s\" (%s): %s"), $article['title'], $id, _("deactivated")), 'horde.success');
    $browse_url->redirect();

break;
case 'activate';

    $result = $news->write_db->query('UPDATE ' . $news->prefix . ' SET status = ? WHERE id = ?', array(News::CONFIRMED, $id));
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    }

    $notification->push(sprintf(_("News \"%s\" (%s): %s"), $article['title'], $id, _("activated")), 'horde.success');
    $browse_url->redirect();

break;
case 'lock';

    $result = $news->write_db->query('UPDATE ' . $news->prefix . ' SET status = ? WHERE id = ?', array(News::LOCKED, $id));
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    }

    $notification->push(sprintf(_("News \"%s\" (%s): %s"), $article['title'], $id, _("locked")), 'horde.success');
    $browse_url->redirect();

break;
case 'unlock';

    $result = $news->write_db->query('UPDATE ' . $news->prefix . ' SET status = ? WHERE id = ?', array(News::UNCONFIRMED, $id));
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    }

    $notification->push(sprintf(_("News \"%s\" (%s): %s"), $article['title'], $id, _("unlocked")), 'horde.success');
    $browse_url->redirect();


break;
case 'renew';

    $version = Horde_Util::getFormData('version');

    $version_data = $news->db->getRow('SELECT content FROM ' . $news->prefix . '_versions WHERE id = ? AND version = ?',
                                      array($id, $version), DB_FETCHMODE_ASSOC);
    if ($version_data instanceof PEAR_Error) {
        $notification->push($version_data);
    }

    $version_data['content'] = unserialize($version_data['content']);
    $result = $news->write_db->query('DELETE FROM ' . $news->prefix . '_body WHERE id = ?', array($id));
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    }

    $new_version = array();
    $sql = 'INSERT INTO ' . $news->prefix . '_body (id,lang,title,abbreviation,content) VALUES (?,?,?,?,?)';

    foreach ($version_data['content'] as $lang => $values) {
        $new_version[$lang] = $values;
        $data = array($id,
                      $lang,
                      $values['title'],
                      substr(strip_tags($values['content']), 0, $conf['preview']['list_content']),
                      $values['content']);
        $news->write_db->query($sql, $data);
    }

    /* save as future version */
    $version = $news->db->getOne('SELECT MAX(version) FROM ' . $news->prefix . '_versions WHERE id = ?', array($id)) + 1;
    $result  = $news->write_db->query('INSERT INTO ' . $news->prefix . '_versions (id, version, created, user_uid, content) VALUES (?,?,NOW(),?,?)',
                                array($id, $version, $GLOBALS['registry']->getAuth(), serialize($new_version)));

    $notification->push(sprintf(_("News \"%s\" (%s): %s"), $article['title'], $id, _("renewed")), 'horde.success');
    $browse_url->redirect();
}

$title = _("Edit");
$vars = Horde_Variables::getDefaultVariables();
$form = new News_Search($vars);
$form->getInfo(null, $info);

/* prepare query */
$binds = $news->buildQuery(Horde_Perms::DELETE, $info);
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
$count = $news->countNews($info, Horde_Perms::DELETE);
if ($count instanceof PEAR_Error) {
    echo $count->getMessage() . ': ' . $count->getDebugInfo();
    exit;
}

// Select rows
$page = Horde_Util::getGet('news_page', 0);
$per_page = $prefs->getValue('per_page');
$sql = $news->db->modifyLimitQuery($sql, $page*$per_page, $per_page);
$rows = $news->db->getAll($sql, $binds[1], DB_FETCHMODE_ASSOC);
if ($rows instanceof PEAR_Error) {
    echo $rows->getMessage() . ': ' . $rows->getDebugInfo();
    exit;
}

// Get pager
$pager = News_Search::getPager($binds[1], $count, $browse_url);

// Output
Horde::addScriptFile('tables.js', 'horde');

require_once NEWS_TEMPLATES . '/common-header.inc';
require_once NEWS_TEMPLATES . '/menu.inc';
require_once NEWS_TEMPLATES . '/edit/header.inc';

$img_dir = Horde_Themes::img(null, 'horde');
foreach ($rows as $row) {
    require NEWS_TEMPLATES . '/edit/row.php';
    if ($row['id'] == $id ) {
        require NEWS_TEMPLATES . '/edit/info.php';
    }
}

require NEWS_TEMPLATES . '/edit/footer.inc';

$form->renderActive(null, null, null, 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
