<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('agora', array('authentication' => 'none'));

// Show a specific scope?
$scope = Horde_Util::getGet('scope', 'agora');
$cache_key = 'agora_rss_' . $scope;

/* Initialize the Cache object. */
$cache = $injector->getInstance('Horde_Cache');
$rss = $cache->get($cache_key, $conf['cache']['default_lifetime']);

if (!$rss) {
    $title = sprintf(_("Forums in %s"), $registry->get('name', $scope));
    $forums = $injector->getInstance('Agora_Factory_Driver')->create($scope);
    $forums_list = $forums->getForums(0, true, 'forum_name', 0);

    $rss = '<?xml version="1.0" encoding="UTF-8" ?>
    <rss version="2.0">
        <channel>
        <title>' . htmlspecialchars($title) . '</title>
        <language>' . str_replace('_', '-', strtolower($registry->preferredLang())) . '</language>
        <lastBuildDate>' . date('r') . '</lastBuildDate>
        <description>' . htmlspecialchars($title) . '</description>
        <link>' . Horde::url('index.php', true, -1) . '</link>
        <generator>' . htmlspecialchars($registry->get('name')) . '</generator>';

    foreach ($forums_list as $forum) {
        $rss .= '
        <item>
            <title>' . htmlspecialchars($forum['forum_name']) . ' </title>
            <description>' . htmlspecialchars($forum['forum_description']) . ' </description>
            <link>' . Horde::url('threads.php', true, -1)->add(array('scope' => $scope, 'forum_id' => $forum['forum_id'])) . '</link>
        </item>';
    }

    $rss .= '
    </channel>
    </rss>';

    $cache->set($cache_key, $rss);
}

header('Content-type: text/xml; charset=UTF-8');
echo $rss;
