<?php
/**
 * Show feed
 *
 * Copyright 2007 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: feed.php 183 2008-01-06 17:39:50Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */
define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';

function _getStories($feed_id)
{
    $stories = $GLOBALS['cache']->get('news_feed_' . $feed_id, $GLOBALS['conf']['cache']['default_lifetime']);
    if (!$stories) {
        $stories = $GLOBALS['registry']->call('news/stories', array($feed_id));
        $GLOBALS['cache']->set('news_feed_' . $feed_id, serialize($stories));
        return $stories;
    } else {
        return unserialize($stories); 
    }
}

$feed_id = Util::getPost('feed_id');
$stories = _getStories($feed_id);
$df = $GLOBALS['prefs']->getValue('date_format');
foreach ($stories as $story) {
    echo strftime($df, $story['story_published'])
        . ' <a href="' . $story['story_url'] . '" target="_blank" title="' . strip_tags($story['story_desc']) . '">' . $story['story_title'] . '</a><br />';
}
