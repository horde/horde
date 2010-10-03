<?php
/**
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('agora', array('authentication' => 'none'));

// Show a specific scope?
$scope = Horde_Util::getGet('scope', 'agora');
$cache_key = 'agora_rss_' . $scope;

/* Initialize the Cache object. */
$cache = $injector->getInstance('Horde_Cache');

$rss = $cache->get($cache_key, $conf['cache']['default_lifetime']);
if (!$rss) {
    $title = sprintf(_("Forums in %s"), $registry->get('name', $scope));
    $forums = Agora_Messages::singleton($scope);
    $forums_list = $forums->getForums(0, true, 'forum_name', 0);

    $rss = '<?xml version="1.0" encoding="' . 'UTF-8' . '" ?>
    <rss version="2.0">
        <channel>
        <title>' . htmlspecialchars($title) . '</title>
        <language>' . str_replace('_', '-', strtolower($registry->preferredLang())) . '</language>
        <lastBuildDate>' . date('r') . '</lastBuildDate>
        <description>' . htmlspecialchars($title) . '</description>
        <link>' . Horde::url('index.php', true, -1) . '</link>
        <generator>' . htmlspecialchars($registry->get('name')) . '</generator>';

    foreach ($forums_list as $forum_id => $forum) {
        $rss .= '
        <item>
            <title>' . htmlspecialchars($forum['forum_name']) . ' </title>
            <description>' . htmlspecialchars($forum['forum_description']) . ' </description>
            <link>' . Horde_Util::addParameter(Horde::url('threads.php', true, -1), array('scope' => $scope, 'forum_id' => $forum_id)) . '</link>
        </item>';
    }

    $rss .= '
    </channel>
    </rss>';

    $cache->set($cache_key, $rss);
}

header('Content-type: text/xml; charset=' . 'UTF-8');
echo $rss;
