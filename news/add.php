<?php
/**
 * Add
 *
 * $Id: add.php 1186 2009-01-21 10:24:00Z duck $
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

/**
 * This routine removes all attributes from a given tag except
 * the attributes specified in the array $attr.
 * Author daneel@neezine.net 22-Aug-2005 05:08
 * http://www.php.net/manual/en/function.strip-tags.php
 *
 * @param string    $msg   text to clean
 * @param string    $tag   tag to clean
 * @param string    $attr  attributest to leave
 *
 * @return string $msg cleaned text
 */
function stripeentag($msg, $tag, $attr = array())
{
    $lengthfirst = 0;
    while (strstr(substr($msg, $lengthfirst), "<$tag ") != "")
    {
        $imgstart = $lengthfirst + strpos(substr($msg,$lengthfirst), "<$tag ");
        $partafterwith = substr($msg, $imgstart);
        $img = substr($partafterwith, 0, strpos($partafterwith, ">") + 1);
        $img = str_replace(" =", "=", $msg);
        $out = "<$tag";

        for ($i=0; $i <= (count($attr) - 1); $i++) {
            $long_val = strpos($img, " ", strpos($img, $attr[$i] . "=")) - (strpos($img, $attr[$i] . "=") + strlen($attr[$i]) + 1);
            $val = substr($img, strpos($img, $attr[$i] . "=") + strlen($attr[$i]) + 1, $long_val);
            if (strlen($val)>0) {
                $attr[$i] = " ".$attr[$i]."=".$val;
            } else {
                $attr[$i] = "";
            }
            $out .= $attr[$i];
        }

        $out .= ">";
        $partafter = substr($partafterwith, strpos($partafterwith, ">") + 1);
        $msg = substr($msg, 0, $imgstart) . $out . $partafter;
        $lengthfirst = $imgstart + 3;
    }

    return $msg;
}

/**
 * Max upload size msg
 */
function _max_upload_size()
{
    static $msg;

    if ($msg) {
        return $msg;
    }

    $filesize = ini_get('upload_max_filesize');
    if (substr($filesize, -1) == 'M') {
        $filesize = $filesize * 1048576;
    }
    $filesize = News::format_filesize($filesize);

    $postsize = ini_get('post_max_size');
    if (substr($postsize, -1) == 'M') {
        $postsize = $postsize * 1048576;
    }
    $postsize = News::format_filesize($postsize);

    $msg = sprintf(_("Maximum file size: %s; with a total of: %s"),
                    $filesize, $postsize);

    return $msg;
}

// Is logged it?
if (!$registry->isAuthenticated()) {
    $notification->push(_("Only authenticated users can post news."), 'horde.warning');
    $registry->authenticateFailure('news');
}

// Default vars
$title = _("Add news");
$default_lang = News::getLang();
$id = Horde_Util::getFormData('id', false);
$return = Horde_Util::getFormData('return', false);

// We just delete default image?
if ($id && Horde_Util::getFormData('submitbutton') == _("Delete existing picture")) {
    $result = $news->write_db->query('UPDATE ' . $news->prefix . ' SET picture = ? WHERE id = ?', array(0, $id));
    if ($sources instanceof PEAR_Error) {
        $notification->push($sources);
    } else {
        News::deleteImage($id);
        News::getUrlFor('news', $id)->redirect();
    }
}

// Prepare form
$vars = Horde_Variables::getDefaultVariables();
$form = new Horde_Form($vars, '', 'addnews');
$form->addHidden('', 'return', 'text', false, true);

if ($id) {
    $form->setButtons(array(_("Update"), _("Delete existing picture")), _("Reset"));
} else {
    $form->setButtons(array(_("Save")), _("Reset"));
}

// General
$form->setSection('content', _("Content"), '', false);
$form->addVariable(_("News content"), 'content', 'header', false);

$v = &$form->addVariable(_("Publish"), 'publish', 'datetime', true, false, false, News::datetimeParams());
$v->setDefault(date('Y-m-d H:i:s'));
$form->addVariable(_("Primary category"), 'category1', 'enum', true, false, false, array($news_cat->getEnum(), _("-- select --")));

// Sources
$sources = $GLOBALS['news']->getSources();
if ($sources instanceof PEAR_Error) {
    $notification->push($sources);
} elseif (!empty($sources)) {
    $form->addVariable(_("Source"), 'source', 'enum', false, false, false, array($sources, _("-- select --")));
}
$form->addVariable(_("Source link"), 'sourcelink', 'text', false, false);

// Languages
foreach ($conf['attributes']['languages'] as $key) {
    $flag = (count($conf['attributes']['languages']) > 1) ? News::getFlag($key) . ' ' : '';
    $form->addVariable($flag . _("Title"), "title_$key", 'text', ($key == $default_lang) ? true : false);
    if ($conf['attributes']['tags']) {
        $form->addVariable($flag . _("Tags"), "tags_$key", 'text', false, false, _("Enter one or more keywords that describe your news. Separate them by spaces."));
    }
    $form->addVariable($flag . _("Content"), "content_$key", 'longtext', ($key == $default_lang) ? true : false , false, false, array(20, 120));
}

// Additional
$form->setSection('attributes', _("Attributes"), '', true);
$form->addVariable(_("Additional news attributes"), 'attributes', 'header', false);
$form->addVariable(_("Secondary category"), 'category2', 'enum', false, false, false, array($news_cat->getEnum(), _("-- select --")));
$form->addVariable(_("Sort order"), 'sortorder', 'enum', false, false, false, array(range(0, 10)));
$form->addVariable(_("Parents"), 'parents', 'intList', false, false, _("Enter news ids separated by commas."));

if ($registry->hasMethod('forums/doComments')) {
    $form->addVariable(sprintf(_("Threads in %s"), $registry->get('name', 'agora')), 'threads', 'intList', false, false, _("Enter threads separated by commas."));
}

$form->setSection('images', _("Images"), '', true);
$form->addVariable(_("News images"), 'content', 'header', false);
$form->addVariable(_max_upload_size(), 'description', 'description', false);

$form->addVariable(_("Picture"), 'picture_0', 'image', false, false, false, array(false));

foreach ($conf['attributes']['languages'] as $key) {
    $flag = (count($conf['attributes']['languages']) > 1) ? News::getFlag($key) . ' ' : '';
    $form->addVariable($flag . _("Picture comment"), "caption_0_$key", 'text', false);
}

// Link to a gallery
if ($conf['attributes']['ansel-images']
    && $registry->hasMethod('images/listGalleries')
    && $registry->images->countGalleries() > 0) {

    $form->addVariable(_("Enter gallery ID or upload images below"), 'description', 'description', false);

    if ($registry->images->countGalleries() > 50) {
        $form->addVariable(_("Gallery"), 'gallery', 'int', false, false);
    } else {
        $ansel_galleries = $registry->images->listGalleries();
        $galleries = array();
        foreach ($ansel_galleries as $gallery_id => $gallery) {
            $galleries[$gallery_id] = $gallery['attribute_name'];
        }
        $form->addVariable(_("Gallery"), 'gallery', 'enum', false, false, false, array($galleries, true));
    }
}

if ($registry->hasMethod('images/listGalleries')) {
    $images = 4;
}

if ($images > 1) {
    $form->addVariable(_("Images will be added to a gallery linked with this article. You can edit and manage images in gallery."), 'description', 'description', false);
    for ($i = 1; $i < $images; $i++) {
        $form->addVariable(_("Picture") . ' ' . $i, 'picture_' . $i, 'image', false, false, false, array(false));
        $form->addVariable(_("Caption") . ' ' . $i, 'caption_' . $i, 'text', false);
    }
}

if ($conf['attributes']['attachments']) {
    $form->setSection('files', _("Files"), '', true);

    $form->addVariable(_max_upload_size(), 'description', 'description', false);

    foreach ($conf['attributes']['languages'] as $key) {
        $flag = (count($conf['attributes']['languages']) > 1) ? News::getFlag($key) . ' ' : '';
        for ($i = 1; $i < 6; $i++) {
            $form->addVariable($flag . ' ' . _("File") . ' ' . $i, 'file_' . $key . '_' . $i, 'file', false);
        }
    }
}

if ($registry->isAdmin(array('permission' => 'news:admin'))) {
    $form->setSection('admin', _("Admin"), '', true);
    $form->addVariable(_("News administrator options"), 'content', 'header', false);

    if ($conf['attributes']['sponsored']) {
        $form->addVariable(_("Sponsored"), 'sponsored', 'boolean', false);
    }

    // Allow commeting this content
    if ($conf['comments']['allow'] != 'never' && $registry->hasMethod('forums/doComments')) {
        $form->addVariable(_("Disallow comments"), 'disable_comments', 'boolean', false, false);
    }

    // Link to selling
    $apis = array();
    foreach ($registry->listAPIs() as $api) {
        if ($registry->hasMethod($api . '/getSellingForm')) {
            $apis[$api] = array();
            try {
                $articles = $registry->call($api  . '/listCostObjects');
                if (!empty($articles)) {
                    foreach ($articles[0]['objects'] as $item) {
                        $apis[$api][$item['id']] = $item['name'];
                    }
                }
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }
        }
    }

    if (!empty($apis)) {
        $v = &$form->addVariable(_("Selling item"), 'selling', 'mlenum', false, false, false, array($apis));
    }

    // Show from
    if ($registry->hasMethod('forms/getForms')) {
        $available = $registry->call('forms/getForms');
        if ($available instanceof PEAR_Error) {
            $notification->push($available, 'horde.warning');
            $available = array();
        }
        $forms = array();
        foreach ($available as $f) {
            $forms[$f['form_id']] = $f['form_name'];
        }

        $form->addVariable(_("Form ID"), 'form_id', 'enum', false, false, false, array($forms, true));
        $form->addVariable(_("Form to"), 'form_ttl', 'datetime', false);
    }
}

// Process form
if ($form->validate()) {

    $status_inserted = false;
    $allowed_cats = $news_cat->getAllowed(Horde_Perms::DELETE);
    $form->getInfo(null, $info);

    // Check permissions
    $info['status'] = News::UNCONFIRMED;
    $info['editor'] = '';
    $info['category1'] = (int)$info['category1'];
    $info['category2'] = (int)@$info['category2'];

    if (!empty($allowed_cats) &&
        (in_array($info['category1'], $allowed_cats) || in_array($info['category2'], $allowed_cats))) {
        $info['editor'] = $GLOBALS['registry']->getAuth();
        $info['status'] = News::CONFIRMED;
    }

    // Multi language
    $info['chars'] = strlen(preg_replace('/\s\s+/', '', trim(strip_tags($info["content_$default_lang"]))));

    foreach ($conf['attributes']['languages'] as $key) {
        if (!$info["title_$key"]) {
            continue;
        }
        $info['body'][$key]['title'] = $info["title_$key"];
        $info['body'][$key]['content'] = $info["content_$key"];
        $info['body'][$key]['caption'] = empty($info["caption_0_$key"]) ? '' : $info["caption_0_$key"];
        $info['body'][$key]['tags'] = $conf['attributes']['tags'] ? $info["tags_$key"] : '';
        unset($info["title_$key"]);
        unset($info["content_$key"]);
        unset($info["caption_$key"]);
        unset($info["tags_$key"]);

        if (strpos($info['body'][$key]['content'], '<') === FALSE) {
            $info['body'][$key]['content'] = nl2br(trim($info['body'][$key]['content']));
        } else {
            $info['body'][$key]['content'] = trim(stripeentag($info['body'][$key]['content'], 'p'));
            $info['body'][$key]['content'] = trim(stripeentag($info['body'][$key]['content'], 'font'));
        }
        $info['chars'] = strlen(strip_tags($info['body'][$key]['content']));
    }

    // Selling
    if (empty($info['selling'][1])) {
        $info['selling'] = '';
    } else {
        $info['selling'] = $info['selling'][1] . '|' . $info['selling'][2];
    }

    // Clean up parents ID
    if ($info['parents']) {
        $info['parents'] = explode(',', trim($info['parents']));
        foreach ($info['parents'] as $i => $parent_id) {
            if (intval($parent_id) == 0) {
                unset($info['parents'][$i]);
            }
        }
        $info['parents'] = implode(',', array_unique($info['parents']));
    }

    // Save as current version
    if (empty($id)) {

        $id = $news->write_db->nextID($news->prefix);
        if ($id instanceof PEAR_Error) {
            $notification->push($id);
            Horde::applicationUrl('browse.php')->redirect();
        }

        $query = 'INSERT INTO ' . $news->prefix
               . ' (sortorder, status, publish, submitted, updated, user, editor, sourcelink, source,'
               . ' sponsored, parents, category1, category2, chars, attachments, gallery, selling,'
               . ' threads, form_id, form_ttl) '
               . ' VALUES (?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $data = array($info['sortorder'],
                      $info['status'],
                      $info['publish'],
                      $GLOBALS['registry']->getAuth(),
                      $info['editor'],
                      @$info['sourcelink'],
                      isset($info['source']) ? $info['source'] : '',
                      empty($info['sponsored']) ? 0 : 1,
                      $info['parents'],
                      $info['category1'],
                      $info['category2'],
                      $info['chars'],
                      sizeof(@$info['attachments']),
                      empty($info['gallery']) ? 0 : $info['gallery'],
                      $info['selling'],
                      $info['threads'],
                      empty($info['form_id']) ? 0 : (int)$info['form_id'],
                      empty($info['form_ttl']) ? 0 : (int)$info['form_ttl']);

        $status_inserted = true;

    } else {

        $query = 'UPDATE ' . $news->prefix . ' SET '
                 . 'sortorder = ?, publish = ?, updated = NOW(), editor = ?, sourcelink = ?, source = ?, '
                 . 'sponsored = ?, parents = ?, category1 = ?, category2 = ?, '
                 . 'chars = ?, attachments = ?, gallery = ?, selling = ?, threads = ?, '
                 . 'form_id = ?, form_ttl = ? WHERE id = ?';

        $data = array($info['sortorder'],
                      $info['publish'],
                      $info['editor'],
                      @$info['sourcelink'],
                      isset($info['source']) ? $info['source'] : '',
                      empty($info['sponsored']) ? 0 : 1,
                      $info['parents'],
                      $info['category1'],
                      $info['category2'],
                      $info['chars'],
                      sizeof(@$info['attachments']),
                      empty($info['gallery']) ? 0 : $info['gallery'],
                      $info['selling'],
                      $info['threads'],
                      empty($info['form_id']) ? 0 : (int)$info['form_id'],
                      empty($info['form_ttl']) ? 0 : (int)$info['form_ttl'],
                      $id);
    }

    $result = $news->write_db->query($query, $data);
    if ($result instanceof PEAR_Error) {
        $notification->push($result->getDebugInfo(), 'horde.error');
        Horde::applicationUrl('edit.php')->redirect();
    }

    // Picture
    $images_uploaded = array();
    if (isset($info['picture_0']['uploaded'])) {
        if ($info['picture_0']['uploaded'] instanceof PEAR_Error) {
            if ($info['picture_0']['uploaded']->getCode() != UPLOAD_ERR_NO_FILE) {
                $notification->push($info['picture_0']['uploaded']->getMessage(), 'horde.warning');
            }
        } else {
            $images_uploaded[] = 0;
            $result = News::saveImage($id, $info['picture_0']['file']);
            if ($result instanceof PEAR_Error) {
                $notification->push($result);
            } else {
                $news->write_db->query('UPDATE ' . $news->prefix . ' SET picture = ? WHERE id = ?', array(1, $id));
            }
        }
    }

    for ($i = 1; $i < $images; $i++) {
        if (isset($info['picture_' . $i]['uploaded'])) {
            if ($info['picture_' . $i]['uploaded'] instanceof PEAR_Error) {
                if ($uploaded->getCode() != UPLOAD_ERR_NO_FILE) {
                    $notification->push($uploaded->getMessage(), 'horde.warning');
                }
            } else {
                $images_uploaded[] = $i;
            }
        }
    }

    // Don't create a galler if no picture or only default one was uploaded
    if (!empty($images_uploaded) &&
        !(count($images_uploaded) == 1 && $images_uploaded[0] == 0)) {

        // Do we have a gallery?
        if (empty($info['gallery'])) {
            $abbr = Horde_String::substr(strip_tags($info['body'][$default_lang]['content']), 0, $conf['preview']['list_content']);
            try {
                $result = $registry->images->createGallery(null,
                                                           array('name' => $info['body'][$default_lang]['title'],
                                                                 'desc' => $abbr));
                $info['gallery'] = $result;
            } catch (Horde_Exception $e) {
                $notification->push(_("There was an error creating gallery: ") . $e->getMessage(), 'horde.warning');
            }
        }

        if (!empty($info['gallery'])) {
            $news->write_db->query('UPDATE ' . $news->prefix . ' SET gallery = ? WHERE id = ?', array($info['gallery'], $id));
            foreach ($images_uploaded as $i) {
                try {
                    $registry->images->saveImage($info['gallery'],
                                                 array('filename' => $info['picture_' . $i]['file'],
                                                       'description' => $info['caption_' . ($i == 0 ? $i . '_' . $default_lang: $i)],
                                                       'type' => $info['picture_' . $i]['type'],
                                                       'data' => file_get_contents($info['picture_' . $i]['file'])));
                } catch (Horde_Exception $e) {
                    $notification->push(_("There was an error with the uploaded image: ") . $e->getMessage(), 'horde.warning');
                }
            }
        }
    }

    // Files
    if ($conf['attributes']['attachments']) {
        $uploaded = false;
        $form->setSection('files', _("Files"), '', true);
        foreach ($conf['attributes']['languages'] as $key) {
            for ($i = 1; $i < 6; $i++) {
                $input = 'file_' . $key . '_' . $i;
                try {
                    $GLOBALS['browser']->wasFileUploaded($input);
                    $file_id = $news->write_db->nextID($news->prefix . '_files');
                    if ($file_id instanceof PEAR_Error) {
                        $notification->push($file_id);
                    } else {
                        $result = News::saveFile($file_id, $info[$input]['file']);
                        if ($result instanceof PEAR_Error) {
                            $notification->push($result->getMessage(), 'horde.warning');
                        } else {
                            $result = $news->write_db->query('INSERT INTO ' . $news->prefix . '_files (file_id, news_id, news_lang, file_name, file_size, file_type) VALUES (?, ?, ?, ?, ?, ?)',
                                        array($file_id, $id, $key, $info[$input]['name'], $info[$input]['size'], $info[$input]['type']));
                            if ($result instanceof PEAR_Error) {
                                $notification->push($result->getMessage(), 'horde.warning');
                            }
                        }
                    }
                } catch (Horde_Browser_Exception $e) {
                    if ($e->getCode() != UPLOAD_ERR_NO_FILE) {
                        $notification->push($e->getMessage(), 'horde.warning');
                    }
                }
            }
        }
        if ($uploaded) {
            $result = $news->write_db->query('UPDATE ' . $news->prefix . ' SET attachments = ? WHERE id = ?', array(1, $id));
            if ($result instanceof PEAR_Error) {
                $notification->push($result->getMessage(), 'horde.warning');
            }
        }
    }

    // Comments
    if (isset($info['disable_comments']) && $info['disable_comments']) {
        $news->write_db->query('UPDATE ' . $news->prefix . ' SET comments = ? WHERE id = ?', array(-1, $id));
    }

    // Bodies
    $news->write_db->query('DELETE FROM ' . $news->prefix . '_body WHERE id = ?', array($id));
    $query_body = $news->write_db->prepare('INSERT INTO ' . $news->prefix . '_body '
                                            . '(id, lang, title, abbreviation, content, picture_comment, tags) VALUES (?, ?, ?, ?, ?, ?, ?)');

    foreach ($info['body'] as $lang => $values) {
        $abbr = Horde_String::substr(strip_tags($values['content']), 0, $conf['preview']['list_content']);
        $news->write_db->execute($query_body, array($id, $lang, $values['title'], $abbr, $values['content'], $values['caption'], $values['tags']));
    }

    // Save as future version
    if ($status_inserted === true) {
        $status_version = 'insert';
    } else {
        $status_version = 'update';
    }
    $version = $news->db->getOne('SELECT MAX(version) FROM ' . $news->prefix . '_versions WHERE id = ?', array($id));
    $result = $news->write_db->query('INSERT INTO ' . $news->prefix . '_versions (id, version, action, created, user_uid, content) VALUES (?, ?, ?, NOW(), ? ,?)',
                                array($id, $version + 1, $status_version, $GLOBALS['registry']->getAuth(), serialize($info['body'])));
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    }

    // Expire newscache
    foreach ($conf['attributes']['languages'] as $key) {
        $cache->expire('news_'  . $key . '_' . $id);
    }

    // Return
    if ($return) {
        $url = $return;
    } elseif (in_array($info['category1'], $allowed_cats) ||
              in_array($info['category2'], $allowed_cats)) {
        $url = Horde_Util::addParameter(Horde::applicationUrl('edit.php'), 'id', $id);
    } else {
        $url = Horde::applicationUrl('browse.php');
    }

    if ($info['status'] != News::CONFIRMED && $status_inserted == true) {
        $notification->push(_("News added. The editors will check the entry and confirm it if they find it suitable."), 'horde.success');
    } elseif ($info['status'] == News::CONFIRMED && $status_inserted == true) {
        $notification->push(_("News published."), 'horde.success');
    } elseif ($status_inserted == false) {
        $notification->push(_("News updated."), 'horde.success');
    }

    $url->redirect();

} elseif ($id && !$form->isSubmitted()) {

    $title = _("Edit news");
    $sql = 'SELECT * FROM ' . $news->prefix . ' WHERE id = ?';
    $result = $news->db->getRow($sql, array($id), DB_FETCHMODE_ASSOC);

    foreach ($result as $key => $value) {
        if ($key == 'picture') {
            continue;
        } elseif ($key == 'comments' && $value == -1) {
            $key = 'disable_comments';
            $value = true;
        } elseif ($key == 'selling') {
            if (empty($value)) {
                continue;
            } else {
                $value = explode('|', $value);
                $vars->set('selling', array(1 => $value[0], 2 => $value[1]));
                continue;
            }
        }

        $vars->set($key, $value);
    }

    $sql = 'SELECT lang, title, content, picture_comment, tags FROM ' . $news->prefix . '_body WHERE id = ?';
    $result = $news->db->getAll($sql, array($id), DB_FETCHMODE_ASSOC);
    foreach ($result as $row) {
        $vars->set('title_' . $row['lang'], $row['title']);
        $vars->set('content_' . $row['lang'], $row['content']);
        $vars->set('caption_0_' . $row['lang'], $row['picture_comment']);
        if ($conf['attributes']['tags']) {
            $vars->set('tags_' . $row['lang'], $row['tags']);
        }
    }

    $form->setButtons(_("Update"), _("Reset"));
}

// Add editor now to avoud JS error notifications no redirect
foreach ($conf['attributes']['languages'] as $key) {
    $injector->getInstance('Horde_Editor')->getEditor('Ckeditor', array('id' => 'content_' . $key));
}

require_once NEWS_TEMPLATES . '/common-header.inc';
require_once NEWS_TEMPLATES . '/menu.inc';
require_once NEWS_TEMPLATES . '/add/before.inc';

$form->renderActive(null, null, Horde_Util::addParameter(Horde::applicationUrl('add.php'), 'id', $id, false), 'post');

require_once $registry->get('templates', 'horde') . '/common-footer.inc';
