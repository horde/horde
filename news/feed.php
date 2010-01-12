<?php
/**
 * Show feed
 *
 * $Id: feed.php 1179 2009-01-20 13:19:34Z duck $
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

$feed_id = Horde_Util::getPost('feed_id');
$stories = _getStories($feed_id);
$df = $GLOBALS['prefs']->getValue('date_format');
foreach ($stories as $story) {
    echo strftime($df, $story['story_published'])
        . ' <a href="' . $story['story_url'] . '" target="_blank" title="' . strip_tags($story['story_desc']) . '">'
        . $story['story_title'] . '</a><br />';
}
