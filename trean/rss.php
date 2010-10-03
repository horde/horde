<?php
/**
 * $Horde: trean/rss.php,v 1.8 2010/02/01 10:32:05 jan Exp $
 *
 * Copyright 2007 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

@define('AUTH_HANDLER', true);
@define('TREAN_BASE', dirname(__FILE__));
require_once TREAN_BASE . '/lib/base.php';
require_once 'Horde/Cache.php';

// Handle HTTP Authentication
function _requireAuth()
{
    $auth = Horde_Auth::singleton($GLOBALS['conf']['auth']['driver']);
    if (!isset($_SERVER['PHP_AUTH_USER'])
        || !$auth->authenticate($_SERVER['PHP_AUTH_USER'],
                                array('password' => isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null))) {
        header('WWW-Authenticate: Basic realm="Trean RSS Interface"');
        header('HTTP/1.0 401 Unauthorized');
        echo '401 Unauthorized';
        exit;
    }

    return true;
}

// Show a specific folder?
if (($folderId = Horde_Util::getGet('f')) !== null) {
    $folder = &$trean_shares->getFolder($folderId);
    // Try guest permissions, if acccess is not granted, login and
    // retry.
    if ($folder->hasPermission('', Horde_Perms::READ) ||
        (_requireAuth() && $folder->hasPermission(Horde_Auth::getAuth(), Horde_Perms::READ))) {
        $folders = array($folderId);
    }
} else {
    // Get all folders. Try guest permissions, if no folders are
    // accessible, login and retry.
    $folders = $trean_shares->listFolders('', Horde_Perms::READ);
    if (empty($folders) && _requireAuth()) {
        $folders = $trean_shares->listFolders(Horde_Auth::getAuth(), Horde_Perms::READ);
    }
}

// No folders to display
if (empty($folders)) {
    exit;
}

// Cache object
$cache = $GLOBALS['injector']->getInstance('Horde_Cache');

// Get folders to display
$cache_key = 'trean_rss_' . Horde_Auth::getAuth() . '_' . ($folderId === null ? 'all' : $folderId);
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

    foreach ($folders as $folderId) {
        $folder = &$trean_shares->getFolder($folderId);
        $bookmarks = $folder->listBookmarks($prefs->getValue('sortby'),
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
    }

    $rss .= '
    </channel>
    </rss>';

    $cache->set($cache_key, $rss);
}

header('Content-type: application/rss+xml');
echo $rss;
