<?php
/**
 * $Id: comments.php 1263 2009-02-01 23:25:56Z duck $
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

$cache_key = 'news_rss_comments';
$rss = $cache->get($cache_key, $conf['cache']['default_lifetime']);
if (!$rss) {
    $list = News::getLastComments(50);
    $title = _("Last comments");

    $rss = '<?xml version="1.0" encoding="' . 'UTF-8' . '" ?>
<rss version="2.0">
<channel>
    <title>' . htmlspecialchars($title) . '</title>
    <language>' . str_replace('_', '-', strtolower($registry->preferredLang())) . '</language>
    <lastBuildDate>' . date('r') . '</lastBuildDate>
    <description>' . htmlspecialchars($title) . '</description>
    <link>' . Horde::url('index.php', true, -1) . '</link>
    <generator>' . htmlspecialchars($registry->get('name')) . '</generator>';

    foreach ($list as $comment) {
        $rss .= '
    <item>
        <title>' . htmlspecialchars($comment['message_subject']) . ' </title>
        <link>' . $comment['read_url'] . '</link>
        <guid isPermaLink="true">' . $comment['read_url'] . '</guid>
        <pubDate>' . date('r', strtotime($comment['message_date'])) . '</pubDate>
        <description><![CDATA[' . $comment['message_author'] . ': ' . strip_tags($comment['body']) . ']]></description>
    </item>';
    }

    $rss .= '
</channel>
</rss>';

    $cache->set($cache_key, $rss);
}

header('Content-type: text/xml; charset=' . 'UTF-8');
echo $rss;
