<?php
/**
 * Delete a news
 *
 * $Id: delete.php 1184 2009-01-21 09:12:20Z duck $
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

if (!$registry->isAdmin(array('permission' => 'news:admin'))) {
    $notification->push(_("Only admin can delete a news."));
    Horde::url('edit.php')->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, _("Are you sure you want to delete this news?"), 'delete');
$form->setButtons(array(_("Remove"), _("Cancel")));

$id = (int)Horde_Util::getFormData('id');
$form->addHidden('', 'id', 'int', $id);

$row = $news->get($id);
$form->addVariable($row['title'], 'news', 'description', true);
$form->addVariable($row['content'], 'content', 'description', true);

if ($form->validate()) {

    if (Horde_Util::getFormData('submitbutton') == _("Remove")) {

        // Delete attachment
        $sql = 'SELECT file_id FROM ' . $news->prefix . '_files WHERE news_id = ?';
        $files = $news->db->getCol($sql, 0, array($id));
        foreach ($files as $file) {
            $result = News::deleteFile($file_id);
            if ($result instanceof PEAR_Error) {
                $notification->push($result);
            }
        }

        // Delete image and gallery
        $sql = 'SELECT picture, gallery FROM ' . $news->prefix . ' WHERE id = ?';
        $image = $news->db->getRow($sql, array($id), DB_FETCHMODE_ASSOC);
        if ($image['picture']) {
            $result = News::deleteImage($id);
            if ($result instanceof PEAR_Error) {
                $notification->push($result);
            }
        }
        if ($image['gallery']) {
            try {
                $registry->call('images/removeGallery', array(null, $image['gallery']));
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }
        }

        // Delete from DB
        $news->write_db->query('DELETE FROM ' . $news->prefix . ' WHERE id = ?', array($id));
        $news->write_db->query('DELETE FROM ' . $news->prefix . '_version WHERE id = ?', array($id));
        $news->write_db->query('DELETE FROM ' . $news->prefix . '_body WHERE id = ?', array($id));
        $news->write_db->query('DELETE FROM ' . $news->prefix . '_user_reads WHERE id = ?', array($id));
        $news->write_db->query('DELETE FROM ' . $news->prefix . '_files WHERE id = ?', array($id));

        // Delete forum
        if ($registry->hasMethod('forums/deleteForum')) {
            try {
                $registry->call('forums/deleteForum', array('news', $id));
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }
        }

        $notification->push(sprintf(_("News %s: %s"), $id, _("deleted")), 'horde.success');

    } else {

        $notification->push(sprintf(_("News %s: %s"), $id, _("not deleted")), 'horde.warning');
    }

    Horde::url('edit.php')->redirect();
}

require NEWS_TEMPLATES . '/common-header.inc';
require NEWS_TEMPLATES . '/menu.inc';
$form->renderActive(null, null, null, 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
