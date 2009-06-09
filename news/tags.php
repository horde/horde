<?php
/**
 * Browse
 *
 * Copyright 2007 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: browse.php 76 2007-12-19 13:57:35Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */
define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';

$remove = array("!", "'", '"', "?", ".", ",", ";", ":", ')', '(', 1, 2, 3, 4, 5, 6, 7, 8, 9, 0, '«', '»', '&', '+', '-', '*');

$result = $news->db->query('UPDATE news_body SET tags = ""');
$result = $news->db->query('SELECT id, title FROM news_body WHERE tags = "" ORDER BY id ASC');
while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
    $row['title'] = Horde_String::lower($row['title'], true);
    $row['title'] = str_replace($remove, '', $row['title']);
    $tags = explode(' ', $row['title']);
    foreach ($tags as $i => $tag) {
        if (strlen($tag) < 4) {
            unset($tags[$i]);
        }
    }
    if (empty($tags)) {
        continue;
    }
    $tags = implode(' ', $tags);
    echo $tags . '<br />';
    $params = array($tags, $row['id']);
    $news->db->query('UPDATE news_body SET tags = ? WHERE id = ?', $params);
}

echo 'done';
