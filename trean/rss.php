<?php
/**
 * Copyright 2007-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('trean');

// Cache object
$cache = $GLOBALS['injector']->getInstance('Horde_Cache');

// Get folders to display
$cache_key = 'trean_rss_' . $registry->getAuth() . '_' . ($folderId === null ? 'all' : $folderId);
$rss = $cache->get($cache_key, $conf['cache']['default_lifetime']);
if (!$rss) {
    $rss = '<?xml version="1.0" encoding="UTF-8" ?>
    <rss version="2.0">
        <channel>
        <title>' . htmlspecialchars($folderId == null ? $registry->get('name') : $folder->get('name')) . '</title>
        <language>' . $registry->preferredLang() . '</language>
        <charset>UTF-8</charset>
        <lastBuildDate>' . date('Y-m-d H:i:s') . '</lastBuildDate>
        <image>
            <url>http://' . $_SERVER['SERVER_NAME'] . $registry->get('webroot') . '/themes/graphics/favicon.ico</url>
        </image>
        <generator>' . htmlspecialchars($registry->get('name')) . '</generator>';

    $bookmarks = $trean_gateway->listBookmarks($prefs->getValue('sortby'),
                                               $prefs->getValue('sortdir'));
    foreach ($bookmarks as $bookmark) {
        if (!$bookmark->url) {
            continue;
        }
        $rss .= '
        <item>
            <title>' . htmlspecialchars($bookmark->title) . ' </title>
            <link>' . htmlspecialchars($bookmark->url) . '</link>
            <description>' . htmlspecialchars($bookmark->description) . '</description>
        </item>';
    }

    $rss .= '
    </channel>
    </rss>';

    $cache->set($cache_key, $rss);
}

header('Content-type: application/rss+xml');
echo $rss;
