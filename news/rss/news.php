<?php
/**
 * $Id: news.php 1263 2009-02-01 23:25:56Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @author McLion <mclion@obala.net>
 */

$news_authentication = 'none';
require_once dirname(__FILE__) . '/../lib/base.php';

$cache_key = 'news_rss_news';
$rss = $cache->get($cache_key, $conf['cache']['default_lifetime']);
if (empty($rss)) {

    /* query preparation */
    $query = 'SELECT n.id, publish, n.user, n.source, n.sourcelink, ' .
             'n.category1, n.category2, n.comments, n.picture, n.chars, nl.content, ' .
             'nl.title, nl.abbreviation ' .
             'FROM ' . $news->prefix . ' AS n, ' . $news->prefix . '_body AS nl ' .
             'WHERE n.status="' . News::CONFIRMED . '" AND n.publish<=NOW() ' .
             'AND nl.lang="' . $registry->preferredLang() . '" AND n.id=nl.id  ORDER BY publish DESC';
    $rssbody = '';
    $query = $news->db->modifyLimitQuery($query, 0, 10);
    $list = $news->db->getAssoc($query, true, array(), DB_FETCHMODE_ASSOC);
    $categories = $news_cat->getCategories(false);
    $title = sprintf(_("Last news"), $registry->get('name', 'horde'));

    $lastnewstime = 0;
    foreach ($list as $news_id => $news) {
        $news_link = News::getUrlFor('news', $news_id, true);
        $rssbody .= '
    <item>
        <enclosure url="http://' . $_SERVER['SERVER_NAME']  . News::getImageUrl($news_id) . '" type="image/jpg" />
        <title>' . htmlspecialchars($news['title']) . ' </title>
        <dc:creator>' . htmlspecialchars($news['user']). '</dc:creator>
        <link>' . $news_link . '</link>
        <guid isPermaLink="true">' . $news_link . '</guid>
        <comments>' . $news_link . '#comments</comments>
        <description><![CDATA[' . trim(substr(htmlspecialchars(strip_tags($news['content'])), 0, 512)) . ']]></description>
        <pubDate>' . date('r', strtotime($news['publish'])) . '</pubDate>
        <category><![CDATA[' . $categories[$news['category1']]['category_name'] . ']]></category>
    </item>';

        if (strtotime($news['publish']) > $lastnewstime) {
            $lastnewstime = strtotime($news['publish']);
        }
    }

    // Wee need the last published news time
    $rssheader = '<?xml version="1.0" encoding="' . 'UTF-8' . '" ?>
<rss version="2.0"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/" >
<channel>
    <title>' . htmlspecialchars($title) . '</title>
    <language>' . str_replace('_', '-', strtolower($registry->preferredLang())) . '</language>
    <lastBuildDate>' . date('r', $lastnewstime) . '</lastBuildDate>
    <description>' . htmlspecialchars($title) . '</description>
    <link>' . Horde::url('index.php', true, -1) . '</link>
    <generator>' . htmlspecialchars($registry->get('name')) . '</generator>';

    $rssfooter = '
</channel>
</rss>';

    // build rss
    $rss = $rssheader . $rssbody . $rssfooter;

    $cache->set($cache_key, $rss);
}

header('Content-type: text/xml;  charset=utf-8');
echo $rss;
