<?php
/**
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$jonah = Horde_Registry::appInit('jonah', array(
    'authentication' => 'none',
    'session_control' => 'readonly'
));

$news = Jonah_News::factory();

// See if the criteria has already been loaded by the index page
$criteria = Horde_Util::nonInputVar('criteria');
if (!$criteria) {
    $criteria = array();
    $criteria['channel_id'] = Horde_Util::getFormData('channel_id');
    $criteria['tag_id'] = Horde_Util::getFormData('tag_id');
    $criteria['feed_type'] = basename(Horde_Util::getFormData('type'));
}

if (empty($criteria['feed_type'])) {
    // If not specified, default to RSS2
    $criteria['feed_type'] = 'rss2';
}

/* Fetch the channel info and the story list and check they are both valid.
 * Do a simple exit in case of errors. */


$channel = $news->getChannel($criteria['channel_id']);
if (is_a($channel, 'PEAR_Error')) {
    Horde::logMessage($channel, 'ERR');
    header('HTTP/1.0 404 Not Found');
    echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested feed (' . htmlspecialchars($criteria['channel_id']) . ') was not found on this server.</p>
</body></html>';
    exit;
}

/* Check for a tag search. */
if (!empty($criteria['tag_id'])) {
    $tag_name = array_shift($news->getTagNames(array($criteria['tag_id'])));
    $stories = $news->searchTagsById(array($criteria['tag_id']), 10, 0, array($criteria['channel_id']));
} else {
    $stories = $news->getStories($criteria['channel_id'], 10, 0, false, time());
}
if (is_a($stories, 'PEAR_Error')) {
    Horde::logMessage($stories, 'ERR');
    $stories = array();
}


$template = new Horde_Template();
$template->set('charset', $GLOBALS['registry']->getCharset());
$template->set('jonah', 'Jonah ' . $registry->getVersion() . ' (http://www.horde.org/jonah/)');
$template->set('xsl', $registry->get('themesuri') . '/feed-rss.xsl');
if (!empty($criteria['tag_id'])) {
    $template->set('channel_name', sprintf(_("Stories tagged with %s in %s"), $tag_name, @htmlspecialchars($channel['channel_name'], ENT_COMPAT, $GLOBALS['registry']->getCharset())));
} else {
    $template->set('channel_name', @htmlspecialchars($channel['channel_name'], ENT_COMPAT, $GLOBALS['registry']->getCharset()));
}
$template->set('channel_desc', @htmlspecialchars($channel['channel_desc'], ENT_COMPAT, $GLOBALS['registry']->getCharset()));
$template->set('channel_updated', htmlspecialchars(date('r', $channel['channel_updated'])));
$template->set('channel_official', htmlspecialchars($channel['channel_official']));
$template->set('channel_rss', htmlspecialchars(Horde_Util::addParameter(Horde::applicationUrl('delivery/rss.php', true, -1), array('type' => 'rss', 'channel_id' => $channel['channel_id']))));
$template->set('channel_rss2', htmlspecialchars(Horde_Util::addParameter(Horde::applicationUrl('delivery/rss.php', true, -1), array('type' => 'rss2', 'channel_id' => $channel['channel_id']))));
foreach ($stories as &$story) {
    $story['story_title'] = @htmlspecialchars($story['story_title'], ENT_COMPAT, $GLOBALS['registry']->getCharset());
    $story['story_desc'] = @htmlspecialchars($story['story_desc'], ENT_COMPAT, $GLOBALS['registry']->getCharset());
    $story['story_link'] = htmlspecialchars($story['story_link']);
    $story['story_permalink'] = (isset($story['story_permalink']) ? htmlspecialchars($story['story_permalink']) : '');
    $story['story_published'] = htmlspecialchars(date('r', $story['story_published']));
    if (!empty($story['story_body_type']) && $story['story_body_type'] == 'text') {
        $story['story_body'] = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($story['story_body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
    }
}
$template->set('stories', $stories);

$browser->downloadHeaders($channel['channel_name'] . '.rss', 'text/xml', true);
$tpl = JONAH_TEMPLATES . '/delivery/' . $criteria['feed_type'];
if (!empty($channel['channel_full_feed'])) {
    $tpl .= '_full';
}
echo $template->fetch($tpl . '.xml');
