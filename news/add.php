<?php
/**
 * Add
 *
 * Copyright 2006 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: add.php 750 2008-08-19 06:03:03Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */

define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';

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

// Is logged it?
if (!Auth::isAuthenticated()) {
    $notification->push(_("Only authenticated users can post news."), 'horde.warning');
    Horde::authenticationFailureRedirect();
}

/* default vars */
$title = _("Add news");
$default_lang = News::getLang();
$id = Util::getFormData('id', false);
$return = Util::getFormData('return', false);

/* prepare form */
$vars = Variables::getDefaultVariables();
$form = &new Horde_Form($vars, '', 'addnews');
$form->_submit = _("Save");
$form->addHidden('', 'return', 'text', false, true);


// General
$form->setSection('content', _("Content"), '', false);
$form->addVariable(_("News content"), 'content', 'header', false);

$v = &$form->addVariable(_("Publish"), 'publish', 'datetime', true, false, false, $news->datetimeParams());
$v->setDefault(date('Y-m-d H:i:s'));
$form->addVariable(_("Primary category"), 'category1', 'enum', true, false, false, array($news_cat->getEnum(), _("-- select --")));

/* Sources */
$sources = $GLOBALS['news']->getSources();
if ($sources instanceof PEAR_Error) {
    $notification->push($sources->getDebugInfo(), 'horde.error');
} elseif (!empty($sources)) {
    $form->addVariable(_("Source"), 'source', 'enum', false, false, false, array($sources, _("-- select --")));
}
$form->addVariable(_("Source link"), 'sourcelink', 'text', false, false);

/* Picture */
$form->addVariable(_("Picture"), 'picture', 'image', false, false, false, array('showUpload' => false));
if ($id) {
    $form->addVariable(_("Picture delete"), 'picture_delete', 'boolean', false, false, _("Delete existing picture"));
}

/* Languages */
foreach ($conf['attributes']['languages'] as $key) {
    $flag = (count($conf['attributes']['languages']) > 1) ? News::getFlag($key) . ' ' : '';
    $form->addVariable($flag . _("Title"), "title_$key", 'text', ($key == $default_lang) ? true : false);
    $form->addVariable($flag . _("Picture comment"), "picture_comment_$key", 'text', false);
    if ($conf['attributes']['tags']) {
        $form->addVariable($flag . _("Tags"), "tags_$key", 'text', false, false, _("Enter one or more keywords that describe your news. Separate them by spaces."));
    }
    $form->addVariable($flag . _("Content"), "content_$key", 'longtext', ($key == $default_lang) ? true : false , false, false, array(20, 120));
    Horde_Editor::singleton('tinymce',
        array('id' => "content_$key",
              'config' => array('mode'=> 'exact',
                                'elements' => "content_$key",
                                'theme' => 'advanced',
                                'plugins' => "xhtmlxtras,paste,media,fullscreen,wordfix",
                                'extended_valid_elements' => "img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style],object,iframe[src|width|height|frameborder]",
                                'paste_auto_cleanup_on_paste' => 'true',
                                'paste_convert_headers_to_strong' => 'true',
                                'theme_advanced_toolbar_location' => 'top',
                                'theme_advanced_toolbar_align' => 'left',
                                'theme_advanced_buttons1' => 'bold,italic,underline,fontsizeselect,separator,strikethrough,sub,sup,bullist,numlist,separator,link,unlink,image,separator,undo,redo,cleanup,code,hr,removeformat,wordfix,fullscreen',
                                'theme_advanced_buttons2' => '',
                                'theme_advanced_buttons3' => '',
                                'theme_advanced_path_location' => 'bottom',
                                'content_css' => '/include/tinymce/screen.css',
                                //'cleanup_on_startup' => 'true',
                                'button_tile_map' => 'true',
                                'theme_advanced_buttons1_add' => 'media',
                                'language' => 'en',
                                'gecko_spellcheck' => 'true',
                                'entity_encoding' => 'raw'
    )));

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

/* Link to a gallery */
if ($conf['attributes']['ansel-images'] 
    && $registry->hasMethod('images/listGalleries')
    && $registry->call('images/countGalleries', array()) > 0) {

    if ($registry->call('images/countGalleries', array()) > 30) {
        $form->addVariable(_("Gallery"), 'gallery', 'int', false);
    } else {
        $ansel_galleries = $registry->call('images/listGalleries', array());
        $galleries = array();
        foreach ($ansel_galleries as $gallery_id => $gallery) {
            $galleries[$gallery_id] = $gallery['attribute_name'];
        }
        $form->addVariable(_("Gallery"), 'gallery', 'enum', false, false, false, array('enum' => $galleries, 'prompt' => true));
    }
}

if (Auth::isAdmin('news:admin')) {

    if ($conf['attributes']['sponsored']) {
        $form->addVariable(_("Sponsored"), 'sponsored', 'boolean', false);
    }

    /* Allow commeting this content */
    if ($conf['comments']['allow'] != 'never' && $registry->hasMethod('forums/doComments')) {
        $form->addVariable(_("Disallow comments"), 'disable_comments', 'boolean', false, false);
    }

    /* Link to selling  */
    $apis = array();
    foreach ($registry->listAPIs() as $api) {
        if ($registry->hasMethod($api . '/getSellingForm')) {
            $apis[$api] = array();
            $articles = $registry->call($api  . '/listCostObjects');
            if ($articles instanceof PEAR_Error) {
                $notification->push($articles->getMessage(), 'horde.error');
            } elseif (!empty($articles)) {
                foreach ($articles[0]['objects'] as $item) {
                    $apis[$api][$item['id']] = $item['name'];
                }
            }
        }
    }

    if (!empty($apis)) {
        $v = &$form->addVariable(_("Selling item"), 'selling', 'mlenum', false, false, false, array($apis));
    }

    /* Show from */
    $available = $GLOBALS['registry']->callByPackage('ulaform', 'getForms');
    if (!($available instanceof PEAR_Error)) {
        $forms = array();
        foreach ($available as $f) {
            $forms[$f['form_id']] = $f['form_name'];
        }

        $form->addVariable(_("Form ID"), 'form_id', 'enum', false, false, false, array($forms, true));
        $form->addVariable(_("Form to"), 'form_ttl', 'datetime', false);
    }
}

/* Files in gollem */
if ($conf['attributes']['attachments'] && $registry->hasMethod('files/setSelectList')) {
    $selectid = Util::getFormData('selectlist_selectid', $registry->call('files/setSelectList'));
    $form->addVariable(_("Attachments"), 'attachments', 'selectfiles', false, false, false, array('link_text' => _("Select files"),
                                                                                                  'link_style' => '',
                                                                                                  'icon' => false,
                                                                                                  'selectid' => $selectid));
}

/* Process form */
if ($form->validate()) {

    $status_inserted = false;
    $allowed_cats = $news_cat->getAllowed(PERMS_DELETE);
    $form->getInfo(null, $info);

    // Check permissions
    $info['status'] = News::UNCONFIRMED;
    $info['editor'] = '';
    $info['category1'] = (int)$info['category1'];
    $info['category2'] = (int)@$info['category2'];

    if (!empty($allowed_cats) &&
        (in_array($info['category1'], $allowed_cats) || in_array($info['category2'], $allowed_cats))) {
        $info['editor'] = Auth::getAuth();
        $info['status'] = News::CONFIRMED;
    }

    /* Multi language */
    $info['chars'] = strlen(preg_replace('/\s\s+/', '', trim(strip_tags($info["content_$default_lang"]))));

    foreach ($conf['attributes']['languages'] as $key) {
        if (!$info["title_$key"]) {
            continue;
        }
        $info['body'][$key]['title'] = $info["title_$key"];
        $info['body'][$key]['content'] = $info["content_$key"];
        $info['body'][$key]['picture_comment'] = $info["picture_comment_$key"];
        $info['body'][$key]['tags'] = $conf['attributes']['tags'] ? $info["tags_$key"] : '';
        unset($info["title_$key"]);
        unset($info["content_$key"]);
        unset($info["picture_comment_$key"]);
        unset($info["tags_$key"]);

        if (strpos($info['body'][$key]['content'], '<') === FALSE) {
            $info['body'][$key]['content'] = nl2br($info['body'][$key]['content']);
        } else {
            $info['body'][$key]['content'] = stripeentag($info['body'][$key]['content'], 'p', array());
            $info['body'][$key]['content'] = stripeentag($info['body'][$key]['content'], 'font', array());
        }
        $info['chars'] = strlen(strip_tags( $info['body'][$key]['content']));
    }

    /* Selling */
    if (empty($info['selling'][1])) {
        $info['selling'] = '';
    } else {
        $info['selling'] = $info['selling'][1] . '|' . $info['selling'][2];
    }

    /* Clean up parents ID */
    if ($info['parents']) {
        $info['parents'] = explode(',', trim($info['parents']));
        foreach ($info['parents'] as $i => $parent_id) {
            if (intval($parent_id) == 0) {
                unset($info['parents'][$i]);
            }
        }
        $info['parents'] = implode(',', array_unique($info['parents']));
    }

    /* save as current version */
    if (empty($id)) {

        $query = 'INSERT INTO ' . $news->prefix
               . ' (sortorder, status, publish, submitted, updated, user, editor, sourcelink, source,'
               . ' sponsored, parents, category1, category2, chars, attachments, gallery, selling,'
               . ' threads, form_id, form_ttl) '
               . ' VALUES (?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $data = array($info['sortorder'],
                      $info['status'],
                      $info['publish'],
                      Auth::getAuth(),
                      $info['editor'],
                      @$info['sourcelink'],
                      isset($info['source']) ? $info['source'] : '',
                      empty($info['sponsored']) ? 0 : 1,
                      $info['parents'],
                      $info['category1'],
                      $info['category2'],
                      $info['chars'],
                      sizeof(@$info['attachments']),
                      isset($galleries) ? $info['gallery'] : 0,
                      $info['selling'],
                      $info['threads'],
                      empty($info['form_id']) ? 0 : (int)$info['form_id'],
                      empty($info['form_ttl']) ? 0 : (int)$info['form_ttl']);
        $status_inserted = true;
    } else {

        $query = 'UPDATE ' . $news->prefix . ' SET '
                 . 'sortorder = ?, publish = ?, updated = NOW(), editor=?, sourcelink = ?, source = ?, '
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
                      isset($galleries) ? $info['gallery'] : 0,
                      $info['selling'],
                      $info['threads'],
                      empty($info['form_id']) ? 0 : (int)$info['form_id'],
                      empty($info['form_ttl']) ? 0 : (int)$info['form_ttl'],
                      $id);
    }

    $result = $news->writedb->query($query, $data);
    if ($result instanceof PEAR_Error) {
        $notification->push($result->getDebugInfo(), 'horde.error');
        header('Location: ' . Horde::applicationUrl('edit.php'));
        exit;
    }

    if (!$id) {
        $id = $news->writedb->getOne('SELECT LAST_INSERT_ID()');
    }

    /* picture */
    if (isset($info['picture_delete']) && $info['picture_delete']) {
        $news->writedb->query('UPDATE ' . $news->prefix . ' SET picture = ? WHERE id = ?', array(0, $id));
    } elseif (getimagesize(@$info['picture']['file']) !== FALSE) {
        News::saveImage($id, $info['picture']['file']);
        
        $news->writedb->query('UPDATE  ' . $news->prefix . '  SET picture = ? WHERE id = ?', array(1, $id));
    }

    /* comments */
    if (isset($info['disable_comments']) && $info['disable_comments']) {
        $news->writedb->query('UPDATE  ' . $news->prefix . '  SET comments = ? WHERE id = ?', array(-1, $id));
    }

    /* bodies */ 
    $news->writedb->query('DELETE FROM ' . $news->prefix . '_body WHERE id=?', array($id));
    $news->writedb->query('DELETE FROM ' . $news->prefix . '_attachment WHERE id=?', array($id));

    $attachments = array();
    if (isset($info['attachments'])) {
        foreach ($info['attachments'] as $file) {
            $attachments[] =  Util::realPath(key($file) . '/' . current($file));
        }
    }

    $query_attach = $news->writedb->prepare('INSERT INTO ' . $news->prefix . '_attachment (id, lang, filename, filesize) VALUES (?, ?, ?, ?)');
    $query_body   = $news->writedb->prepare('INSERT INTO ' . $news->prefix . '_body '
                                            . '(id, lang, title, abbreviation, content, picture_comment, tags) VALUES (?, ?, ?, ?, ?, ?, ?)');

    foreach ($info['body'] as $lang => $values) {
        $abbr = String::substr(strip_tags($values['content']), 0, $conf['preview']['list_content']);
        $news->writedb->execute($query_body, array($id, $lang, $values['title'], $abbr, $values['content'], $values['picture_comment'], $values['tags']));
        if (isset($info['attachments'])) {
            for ($i = 0; $i < sizeof($info['attachments']); $i++) {
                $f = $conf['attributes']['attachments'] . $attachments[$i];
                $size = filesize($f);
                if ($size) {
                    $news->writedb->execute($query_attach, array($id, $lang, $attachments[$i], $size));
                } else {
                    $notification->push(sprintf(_("Cannot access file %s"), $f), 'horde.warning');
                }
            }
        }
    }

    /* save as future version */
    if ($status_inserted === true) {
        $status_version = 'insert';
    } else {
        $status_version = 'update';
    }
    $version = $news->db->getOne('SELECT MAX(version) FROM ' . $news->prefix . '_versions WHERE id = ?', array($id));
    $result  = $news->writedb->query('INSERT INTO ' . $news->prefix . '_versions (id, version, action, created, user_uid, content) VALUES (?,?,?,NOW(),?,?)',
                                array($id, $version + 1, $status_version, Auth::getAuth(), serialize($info['body'])));
    if ($result instanceof PEAR_Error) {
        $notification->push($result->getMessage(), 'horde.error');
    }

    /* Expire newscache */
    foreach ($conf['attributes']['languages'] as $key) {
            $cache->expire('news_'  . $key . '_' . $id);
    }

    /* return */
    if ($return) {
        $url = $return;
    } elseif (in_array($info['category1'], $allowed_cats) ||
              in_array($info['category2'], $allowed_cats)) {
        $url = Util::addParameter(Horde::applicationUrl('edit.php'), 'id', $id);
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

    header('Location: ' . $url);
    exit;

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
        $vars->set('picture_comment_' . $row['lang'], $row['picture_comment']);
        if ($conf['attributes']['tags']) {
            $vars->set('tags_' . $row['lang'], $row['tags']);
        }
    }

    if ($conf['attributes']['attachments'] && $registry->hasMethod('files/setSelectList')) {
        $sql = 'SELECT lang, filename FROM ' . $news->prefix . '_attachment WHERE id = ?';
        $result = $news->db->getAll($sql, array($id), DB_FETCHMODE_ASSOC);
        $files = array();
        if (sizeof($result)>0) {
            foreach ($result as $row) {
                $files[] = array(dirname($row['filename']) => basename($row['filename']));
            }
        }

        $registry->call('files/setSelectList', array($selectid, $files));
    }

    $form->_submit = _("Update");
    $form->_reset = _("Reset");
}

/* display */
require_once NEWS_TEMPLATES . '/common-header.inc';
require_once NEWS_TEMPLATES . '/menu.inc';
require_once NEWS_TEMPLATES . '/add/before.inc';

$form->renderActive(null, null, Util::addParameter(Horde::applicationUrl('add.php'), 'id', $id, false), 'post');

require_once $registry->get('templates', 'horde') . '/common-footer.inc';
