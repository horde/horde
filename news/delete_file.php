<?php
/**
 * Delete a news
 *
 * $Id: delete_file.php 1186 2009-01-21 10:24:00Z duck $
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
$form = new Horde_Form($vars, _("Are you sure you want to delete file?"), 'delete');
$form->setButtons(array(_("Remove"), _("Cancel")));

$news_id = (int)Horde_Util::getFormData('news_id');
$form->addHidden('', 'news_id', 'int', true);

$news_lang = Horde_Util::getFormData('lang', News::getLang());
$form->addHidden('', 'news_lang', 'text', false);

$file_id = Horde_Util::getFormData('file_id');
$form->addHidden('', 'file_id', 'text', true);

$article = $news->get($news_id);
$files = $news->getFiles($news_id);
foreach ($files as $file) {
    if ($file['file_id'] == $file_id) {
        break;
    }
}

$form->addVariable($file['file_name'], 'file_name', 'description', false);
$form->addVariable(News::format_filesize($file['file_size']), 'file_size', 'description', false);
$form->addVariable($article['title'], 'news', 'description', false);
$form->addVariable($article['content'], 'content', 'description', false);

if ($form->validate()) {

    if (Horde_Util::getFormData('submitbutton') == _("Remove")) {
        $result = News::deleteFile($file_id);
        if ($result instanceof PEAR_Error) {

            $notification->push(sprintf(_("Error deleteing file \"%s\" from news \"%s\""), $file_id['file_name'], $article['title']), 'horde.success');

        } else {

            $result = $news->write_db->query('DELETE FROM ' . $news->prefix . '_files WHERE file_id = ?', array($file_id));
            if ($result instanceof PEAR_Error) {
                $notification->push($result);
            }

            $count = $news->db->getOne('SELECT COUNT(*) FROM ' . $news->prefix . '_files WHERE news_id = ?', array($news_id));
            if ($count instanceof PEAR_Error) {
                $notification->push($count);
            }

            $result = $news->write_db->query('UPDATE ' . $news->prefix . ' SET attachments = ? WHERE id = ?', array($count, $news_id));
            if ($result instanceof PEAR_Error) {
                $notification->push($result);
            }

            $notification->push(sprintf(_("File \"%s\" was deleted from news \"%s\""), $file_id['file_name'], $article['title']), 'horde.success');

            $cache->expire('news_'  . $news_lang . '_' . $news_id);
        }

    } else {

        $notification->push(sprintf(_("File \"%s\" was not deleted from news \"%s\""), $file_id['file_name'], $article['title']), 'horde.success');

    }

    News::getUrlFor('news', $news_id)->redirect();
}

require NEWS_TEMPLATES . '/common-header.inc';
require NEWS_TEMPLATES . '/menu.inc';
$form->renderActive(null, null, null, 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
