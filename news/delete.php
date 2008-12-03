<?php
/**
 * Delete an news
 *
 * $Id: delete.php 229 2008-01-12 19:47:30Z duck $
 *
 * NEWS: Copyright 2007 Duck <duck@obala.net>
 *
 * @author  Duck <duck@obala.net>
 * @package NEWS
*/
define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';

if (!Auth::isAdmin('news:admin')) {
    $notification->push(_("Only admin can delete a news."));
    header('Location: ' . Horde::applicationUrl('edit.php'));
    exit;
};

require_once 'Horde/Form.php';
require_once 'Horde/Variables.php';

$vars = Variables::getDefaultVariables();
$form = new Horde_Form($vars, _("Are you sure you want to delete this news?"), 'delete');
$form->setButtons(array(_("Remove"), _("Cancel")));

$id = (int)Util::getFormData('id');
$form->addHidden('', 'id', 'int', $id);

if ($form->validate()) {

    if (Util::getFormData('submitbutton') == _("Remove")) {

        $news->writedb->query('DELETE FROM ' . $news->prefix . ' WHERE id=?', array($id));
        $news->writedb->query('DELETE FROM ' . $news->prefix . '_version WHERE id=?', array($id));
        $news->writedb->query('DELETE FROM ' . $news->prefix . '_body WHERE id=?', array($id));
        $news->writedb->query('DELETE FROM ' . $news->prefix . '_user_reads WHERE id=?', array($id));

        // Delete attachment
        $sql = 'SELECT filename FROM ' . $news->prefix . '_attachment WHERE id = ?';
        $files = $news->db->getCol($sql, 0, array($id));
        foreach ($files as $file) {
            unlink($conf['attributes']['attachments'] . $file);
        }
        $news->writedb->query('DELETE FROM ' . $news->prefix . '_attachment WHERE id=?', array($id));
        // Delete forum
        if ($registry->hasMethod('forums/deleteForum')) {
            $comments = $registry->call('forums/deleteForum', array('news', $id));
            if ($comments instanceof PEAR_Error) {
                $notification->push($comments->getMessage(), 'horde.error');
            }
        }

        $notification->push(sprintf(_("News %s: %s"), $id, _("deleted")), 'horde.success');
    } else {
        $notification->push(sprintf(_("News %s: %s"), $id, _("not deleted")), 'horde.warning');
    }
    header('Location: ' . Horde::applicationUrl('edit.php'));
    exit;
}

require NEWS_TEMPLATES . '/common-header.inc';
require NEWS_TEMPLATES . '/menu.inc';

$form->renderActive(null, null, null, 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
