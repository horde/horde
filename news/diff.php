<?php
/**
 * Diff
 *
 * $Id: diff.php 803 2008-08-27 08:29:20Z duck $
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

$title = _("Diff");
$id = Horde_Util::getFormData('id', 0);
$version = Horde_Util::getFormData('version', 0);

/* Set up the diff renderer. */
$render_type = Horde_Util::getFormData('render', 'inline');
$class = 'Text_Diff_Renderer_' . $render_type;
$renderer = new $class();

/* get current version content */
$current_data = array();
$result = $news->db->getAll('SELECT lang, title, content FROM ' . $news->prefix . '_body WHERE id = ?', array($id), DB_FETCHMODE_ASSOC);
if ($result instanceof PEAR_Error) {
    var_dump($result);
    exit;
}

foreach ($result as $row) {
    $current_data[$row['lang']]['title'] = $row['title'];
    $current_data[$row['lang']]['content'] = $row['content'];
}

/* get version data */
$version_data = $news->db->getOne('SELECT content FROM ' . $news->prefix . '_versions WHERE id = ? AND version = ?', array($id, $version));
if ($version_data instanceof PEAR_Error) {
    var_dump($version_data);
    exit;
}

$version_data = unserialize($version_data);

Horde::includeStylesheetFiles();

while (list($k, $v) = each($current_data)) {
    echo '<hr><strong>' . $nls['languages'][$k] . '</strong><hr>' . "\n";
    $to = explode("\n", @htmlentities(strip_tags($v['content'])));
    $from = explode("\n", @htmlentities(strip_tags($version_data[$k]['content'])));
    $diff = new Text_Diff($from, $to);
    if (!empty($diff)) {
       echo nl2br($renderer->render($diff));
    } else {
       return _("No change.");
    }
}
