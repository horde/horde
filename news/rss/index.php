<?php
/**
 * $Id: index.php 183 2008-01-06 17:39:50Z duck $
 *
 * Copyright 2007 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */

$news_authentication = 'none';
require_once dirname(__FILE__) . '/../lib/base.php';

// Show a specific user?
$cache_key = 'news_rss_index';

$rss = $cache->get($cache_key, $conf['cache']['default_lifetime']);
if (!$rss) {

    $title = $registry->get('name', 'horde');

    $read_url = Horde::url('read.php', true, -1);
    $rss = '<?xml version="1.0" encoding="' . 'UTF-8' . '" ?>
    <rss version="2.0">
        <channel>
        <title>' . htmlspecialchars($title) . '</title>
        <language>' . str_replace('_', '-', strtolower($registry->preferredLang())) . '</language>
        <lastBuildDate>' . date('r') . '</lastBuildDate>
        <description>' . htmlspecialchars($title) . '</description>
        <link>' . Horde::url('index.php', true, -1) . '</link>
        <generator>' . htmlspecialchars($registry->get('name')) . '</generator>';

    $rss .= '
    <item>
        <title>' . _("Last news") . ' </title>
        <link>' . Horde::url('rss/news.php', true, -1) . '</link>
    </item>';

    $rss .= '
    <item>
        <title>' . _("Last comments") . ' </title>
        <link>' . Horde::url('rss/comments.php', true, -1) . '</link>
    </item>';

    $rss .= '
    </channel>
    </rss>';

    $cache->set($cache_key, $rss);
}

header('Content-type: text/xml');
echo $rss;
