<?php
/**
 * @package Jonah
 */

/** Horde_Array */
require_once 'Horde/Array.php';

/**
 * Jonah_Driver:: is responsible for storing, searching, sorting and filtering
 * locally generated and managed articles.  Aggregation is left to Hippo.
 *
 * $Horde: jonah/lib/Driver.php,v 1.7 2009/07/09 08:18:26 slusarz Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Jonah
 */
class Jonah_Driver {

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Constructs a new Driver storage object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Jonah_Driver($params = array())
    {
        $this->_params = $params;
    }

    /**
     */
    function deleteChannel(&$info)
    {
        return $this->_deleteChannel($info['channel_id']);
    }

    /**
     * Fetches the requested channel, while actually passing on the request to
     * the backend _getChannel() function to do the real work.
     *
     * @param integer $channel_id  The channel id to fetch.
     *
     * @return array|PEAR_Error  The channel details as an array or a
     *                           PEAR_Error if not valid or not found.
     */
    function getChannel($channel_id)
    {
        static $channel = array();

        /* We need a non empty channel id. */
        if (empty($channel_id)) {
            return PEAR::raiseError(_("Missing channel id."));
        }

        /* Cache the fetching of channels. */
        if (!isset($channel[$channel_id])) {
            $channel[$channel_id] = $this->_getChannel($channel_id);
            if (!is_a($channel[$channel_id], 'PEAR_Error')) {
                if (empty($channel[$channel_id]['channel_link'])) {
                    $channel[$channel_id]['channel_official'] = Horde_Util::addParameter(Horde::applicationUrl('delivery/html.php', true, -1), 'channel_id', $channel_id, false);
                } else {
                    $channel[$channel_id]['channel_official'] = str_replace(array('%25c', '%c'), array('%c', $channel_id), $channel[$channel_id]['channel_link']);
                }
            }
        }

        return $channel[$channel_id];
    }

    /**
     * Returns the most recent or all stories from a channel.
     *
     * @param integer $criteria    An associative array of attributes on which
     *                             the resulting stories should be filtered.
     *                             Examples:
     *                             'channel' => string Channel slug
     *                             'channel_id' => int Channel ID
     *                             'author' => string Story author
     *                             'updated-min' => Horde_Date Only return
     *                                                         stories updated
     *                                                         on or after this
     *                                                         date
     *                             'updated-max' => Horde_Date Only return
     *                                                         stories updated
     *                                                         on or before this
     *                                                         date
     *                             'published-min' => Horde_Date Only return
     *                                                           stories
     *                                                           published on or
     *                                                           after this date
     *                             'published-max' => Horde_Date Only return
     *                                                           stories
     *                                                           published on or
     *                                                           before date
     *                             'published-max' => Horde_Date Only return
     *                                                         on or before this
     *                                                         date
     *                             'tags' => array Array of tag names ANY of
     *                                             which may match the story to
     *                                             be included
     *                             'alltags' => array Array of tag names ALL of
     *                                                which must be associated
     *                                                with the story to be
     *                                                included
     *                             'keywords' => array Array of strings ALL of
     *                                                 which matching must
     *                                                 include
     *                             'published' => boolean Whether to return only
     *                                                    published stories;
     *                                                    null will return both
     *                                                    published and
     *                                                    unpublished
     *                             'startnumber' => int Story number to begin
     *                             'endnumber' => int Story number to end
     *                             'limit' => int Max number of stories
     *
     * @param integer $order       How to order the results for internal
     *                             channels. Possible values are the
     *                             JONAH_ORDER_* constants.
     *
     * @return array  The specified number (or less, if there are fewer) of
     *                stories from the given channel.
     */
    function getStories($criteria)
    {
        // Convert a channel slug into a channel ID if necessary
        if (isset($criteria['channel']) && !isset($criteria['channel_id'])) {
            $criteria['channel_id'] = $this->getIdBySlug($criteria['channel']);
        }


        // Validate that we have proper Horde_Date objects
        if (isset($criteria['updated-min'])) {
            if (!is_a($criteria['updated-min'], 'Horde_Date')) {
                throw new Exception("Invalid date object provided for update start date.");
            }
        }
        if (isset($criteria['updated-max'])) {
            if (!is_a($criteria['updated-max'], 'Horde_Date')) {
                throw new Exception("Invalid date object provided for update end date.");
            }
        }
        if (isset($criteria['published-min'])) {
            if (!is_a($criteria['published-min'], 'Horde_Date')) {
                throw new Exception("Invalid date object provided for published start date.");
            }
        }
        if (isset($criteria['published-max'])) {
            if (!is_a($criteria['published-max'], 'Horde_Date')) {
                throw new Exception("Invalid date object provided for published end date.");
            }
        }

        // Collect the applicable tag IDs
        $criteria['tagIDs'] = array();
        if (isset($criteria['tags'])) {
            $criteria['tagIDs'] = array_merge($criteria['tagIDs'], $this->getTagIds($criteria['tags']));
        }
        if (isset($criteria['alltags'])) {
            $criteria['tagIDs'] = array_merge($criteria['tagIDs'], $this->getTagIds($criteria['alltags']));
        }

        return $this->_getStories($criteria);
    }

    /**
     * Returns the most recent or all stories from a channel.
     * This method is deprecated.
     *
     * @param integer $channel_id  The news channel to get stories from.
     * @param integer $max         The maximum number of stories to get. If
     *                             null, all stories will be returned.
     * @param integer $from        The number of the story to start with.
     * @param boolean $refresh     Force a refresh of stories in case this is
     *                             an external channel.
     * @param integer $date        The timestamp of the date to start with.
     * @param boolean $unreleased  Return stories that have not yet been
     *                             published?
     *                             Defaults to false - only published stories.
     * @param integer $order       How to order the results for internal
     *                             channels. Possible values are the
     *                             JONAH_ORDER_* constants.
     *
     * @return array  The specified number (or less, if there are fewer) of
     *                stories from the given channel.
     */
    function legacyGetStories($channel, $max = 10, $from = 0, $refresh = false,
                        $date = null, $unreleased = false,
                        $order = JONAH_ORDER_PUBLISHED)
    {
        global $conf, $registry;

        $channel['channel_link'] = Horde_Util::addParameter(Horde::applicationUrl('delivery/html.php', true, -1), 'channel_id', $channel['channel_id']);
        $stories = $this->_legacyGetStories($channel['channel_id'], $max, $from, $date, $unreleased, $order);
        if (is_a($stories, 'PEAR_Error')) {
            return $stories;
        }
        $date_format = $GLOBALS['prefs']->getValue('date_format');
        $comments = $conf['comments']['allow'] && $registry->hasMethod('forums/numMessages');
        foreach ($stories as $key => $story) {
            $stories[$key]['story_link'] = $this->getStoryLink($channel, $story);
            $stories[$key]['story_updated'] = $story['story_updated'];
            $stories[$key]['story_updated_date'] = strftime($date_format, $story['story_updated']);
            if ($comments) {
                $stories[$key]['num_comments'] = $registry->call('forums/numMessages', array($story['story_id'], $registry->getApp()));
                if (is_a($stories[$key]['num_comments'], 'PEAR_Error')) {
                    $stories[$key]['num_comments'] = null;
                }
            }
            $stories[$key] = array_merge($channel, $stories[$key]);
        }

        return $stories;
    }

    /**
     */
    function _escapeExternalStories(&$story, $key, $channel)
    {
        $story = array_merge($channel, $story);
        $story['story_link'] = Horde::externalUrl($story['story_url']);
    }

    /**
     */
    function saveStory(&$info)
    {
        /* Used for checking whether to send out delivery or not. */
        if (empty($info['story_published'])) {
            /* Story is not being released. */
            $deliver = false;
        } elseif (empty($info['story_id'])) {
            /* Story is new. */
            $deliver = true;
        } else {
            /* Story is old, has it been released already? */
            $oldstory = $this->getStory(null, $info['story_id']);
            if ((empty($oldstory['story_published']) ||
                 $oldstory['story_published'] > $oldstory['story_updated']) &&
                $info['story_published'] <= time()) {
                $deliver = true;
            } else {
                $deliver = false;
            }
        }

        /* First save to the backend. */
        $result = $this->_saveStory($info);
        if (is_a($result, 'PEAR_Error') || !$deliver) {
            /* Return here also if editing, do not bother doing deliveries for
             * an edited story. */
            return $result;
        }
    }

    /**
     */
    function getStory($channel_id, $story_id, $read = false)
    {
        $channel = null;
        if ($channel_id) {
            $channel = $this->getChannel($channel_id);
            if (is_a($channel, 'PEAR_Error')) {
                return $channel;
            }
            if ($channel['channel_type'] == JONAH_EXTERNAL_CHANNEL) {
                return $this->_getExternalStory($channel, $story_id);
            }
        }

        $story = $this->_getStory($story_id, $read);
        if (is_a($story, 'PEAR_Error')) {
            return $story;
        }

        /* Format story link. */
        $story['story_link'] = $this->getStoryLink($channel, $story);

        /* Format dates. */
        $date_format = $GLOBALS['prefs']->getValue('date_format');
        $story['story_updated_date'] = strftime($date_format, $story['story_updated']);
        if (!empty($story['story_published'])) {
            $story['story_published_date'] = strftime($date_format, $story['story_published']);
        }

        return $story;
    }

    /**
     * Returns the official link to a story.
     *
     * @param array $channel  A channel hash.
     * @param array $story    A story hash.
     *
     * @return string  The story link.
     */
    function getStoryLink($channel, $story)
    {
        if ((empty($story['story_url']) || !empty($story['story_body'])) &&
            !empty($channel['channel_story_url'])) {
            $url = $channel['channel_story_url'];
        } else {
            $url = Horde_Util::addParameter(Horde::applicationUrl('stories/view.php', true, -1), array('channel_id' => '%c', 'story_id' => '%s'), null, false);
        }
        return str_replace(array('%25c', '%25s', '%c', '%s'),
                           array('%c', '%s', $channel['channel_id'], $story['story_id']),
                           $url);
    }

    /**
     */
    function getChecksum($story)
    {
        return md5($story['story_title'] . $story['story_desc']);
    }

    /**
     */
    function getIntervalLabel($seconds = null)
    {
        $interval = array(1 => _("none"),
                          1800 => _("30 mins"),
                          3600 => _("1 hour"),
                          7200 => _("2 hours"),
                          14400 => _("4 hours"),
                          28800 => _("8 hours"),
                          43200 => _("12 hours"),
                          86400 => _("24 hours"));

        if ($seconds === null) {
            return $interval;
        } else {
            return $interval[$seconds];
        }
    }

    /**
     * Returns the stories of a channel rendered with the specified template.
     *
     * @param integer $channel_id  The news channel to get stories from.
     * @param string  $tpl         The name of the template to use.
     * @param integer $max         The maximum number of stories to get. If
     *                             null, all stories will be returned.
     * @param integer $from        The number of the story to start with.
     * @param integer $order       How to sort the results for internal channels
     *                             Possible values are the JONAH_ORDER_*
     *                             constants.
     *
     * @return string  The rendered story listing.
     */
    function renderChannel($channel_id, $tpl, $max = 10, $from = 0, $order = JONAH_ORDER_PUBLISHED)
    {
        $channel = $this->getChannel($channel_id);
        if (is_a($channel, 'PEAR_Error')) {
            return sprintf(_("Error fetching feed: %s"), $channel->getMessage());
        }

        include JONAH_BASE . '/config/templates.php';
        $escape = !isset($templates[$tpl]['escape']) ||
            !empty($templates[$tpl]['escape']);
        $template = new Horde_Template();

        if ($escape) {
            $channel['channel_name'] = htmlspecialchars($channel['channel_name']);
            $channel['channel_desc'] = htmlspecialchars($channel['channel_desc']);
        }
        $template->set('channel', $channel, true);

        /* Get one story more than requested to see if there are more
         * stories. */
        if ($max !== null) {
            $stories = $this->getStories($channel_id, $max + 1, $from, false, time(), false, $order);
            if (is_a($stories, 'PEAR_Error')) {
                return $stories->getMessage();
            }
        } else {
            $stories = $this->getStories($channel_id, null, 0, false, time(), false, $order);
            if (is_a($stories, 'PEAR_Error')) {
                return $stories->getMessage();
            }
            $max = count($stories);
        }

        if (!$stories) {
            $template->set('error', _("No stories are currently available."), true);
            $template->set('stories', false, true);
            $template->set('image', false, true);
            $template->set('form', false, true);
        } else {
            /* Escape. */
            if ($escape) {
                array_walk($stories, array($this, '_escapeStories'));
            }

            /* Process story summaries. */
            array_walk($stories, array($this, '_escapeStoryDescriptions'));

            $template->set('error', false, true);
            $template->set('story_marker', Horde::img('story_marker.png'));
            $template->set('image', false, true);
            $template->set('form', false, true);
            if ($from) {
                $template->set('previous', max(0, $from - $max), true);
            } else {
                $template->set('previous', false, true);
            }
            if ($from && !empty($channel['channel_page_link'])) {
                $template->set('previous_link',
                               str_replace(
                                   array('%25c', '%25n', '%c', '%n'),
                                   array('%c', '%n', $channel['channel_id'], max(0, $from - $max)),
                                   $channel['channel_page_link']),
                               true);
            } else {
                $template->set('previous_link', false, true);
            }
            $more = count($stories) > $max;
            if ($more) {
                $template->set('next', $from + $max, true);
                array_pop($stories);
            } else {
                $template->set('next', false, true);
            }
            if ($more && !empty($channel['channel_page_link'])) {
                $template->set('next_link',
                               str_replace(
                                   array('%25c', '%25n', '%c', '%n'),
                                   array('%c', '%n', $channel['channel_id'], $from + $max),
                                   $channel['channel_page_link']),
                               true);
            } else {
                $template->set('next_link', false, true);
            }

            $template->set('stories', $stories, true);
        }

        return $template->parse($templates[$tpl]['template']);
    }

    /**
     */
    function _escapeStories(&$value, $key)
    {
        $value['story_title'] = htmlspecialchars($value['story_title']);
        $value['story_desc'] = htmlspecialchars($value['story_desc']);
        if (isset($value['story_link'])) {
            $value['story_link'] = htmlspecialchars($value['story_link']);
        }
        if (empty($value['story_body_type']) || $value['story_body_type'] != 'richtext') {
            $value['story_body'] = htmlspecialchars($value['story_body']);
        }
    }

    /**
     */
    function _escapeStoryDescriptions(&$value, $key)
    {
        $value['story_desc'] = nl2br($value['story_desc']);
    }

    /**
     * Returns the provided story as a MIME part.
     *
     * @param array $story  A data array representing a story.
     *
     * @return MIME_Part  The MIME message part containing the story parts.
     */
    function &getStoryAsMessage(&$story)
    {
        require_once 'Horde/MIME/Part.php';

        /* Add the story to the message based on the story's body type. */
        switch ($story['story_body_type']) {
        case 'richtext':
            /* Get a plain text version of a richtext story. */
            $body_html = $story['story_body'];
            $body_text = Horde_Text_Filter::filter($body_html, 'html2text');

            /* Add description. */
            $body_html = '<p>' . Horde_Text_Filter::filter($story['story_desc'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO, 'charset' => Horde_Nls::getCharset(), 'class' => null, 'callback' => null)) . "</p>\n" . $body_html;
            $body_text = Horde_String::wrap('  ' . $story['story_desc'], 70) . "\n\n" . $body_text;

            /* Add the text version of the story to the base message. */
            $message_text = new MIME_Part('text/plain');
            $message_text->setCharset(Horde_Nls::getCharset());
            $message_text->setContents($message_text->replaceEOL($body_text));
            $message_text->setDescription(_("Plaintext Version of Story"));

            /* Add an HTML version of the story to the base message. */
            $message_html = new MIME_Part('text/html', Horde_String::wrap($body_html),
                                          Horde_Nls::getCharset(), 'inline');
            $message_html->setDescription(_("HTML Version of Story"));

            /* Add the two parts as multipart/alternative. */
            $basepart = new MIME_Part('multipart/alternative');
            $basepart->addPart($message_text);
            $basepart->addPart($message_html);

            return $basepart;

        case 'text':
            /* This is just a plain text story. */
            $message_text = new MIME_Part('text/plain');
            $message_text->setContents($message_text->replaceEOL($story['story_desc'] . "\n\n" . $story['story_body']));
            $message_text->setCharset(Horde_Nls::getCharset());

            return $message_text;
        }
    }

    /**
     * Attempts to return a concrete Jonah_Driver instance based on $driver.
     *
     * @param string $driver  The type of concrete Jonah_Driver subclass to
     *                        return. The is based on the storage driver
     *                        ($driver). The code is dynamically included.
     *
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The newly created concrete Jonah_Driver instance, or false
     *                on an error.
     */
    function factory($driver = null, $params = null)
    {
        if ($driver === null) {
            $driver = $GLOBALS['conf']['news']['storage']['driver'];
        }
        $driver = basename($driver);

        if ($params === null) {
            $params = Horde::getDriverConfig(array('news', 'storage'), $driver);
        }

        $class = 'Jonah_Driver_' . $driver;
        if (!class_exists($class, false)) {
            include dirname(__FILE__) . '/Driver/' . $driver . '.php';
        }
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return PEAR::raiseError(sprintf(_("No such backend \"%s\" found"), $driver));
        }
    }

    /**
     * Stubs for the tag functions. If supported by the backend, these need
     * to be implemented in the concrete Jonah_Driver_* class.
     */
    function writeTags($resource_id, $channel_id, $tags)
    {
        return PEAR::raiseError(_("Tag support not enabled in backend."));
    }

    function readTags($resource_id)
    {
        return PEAR::raiseError(_("Tag support not enabled in backend."));
    }

    function listTagInfo($tags = array(), $channel_id = null)
    {
        return PEAR::raiseError(_("Tag support not enabled in backend."));
    }

    function searchTagsById($ids, $max = 10, $from = 0, $channel_id = array(),
                            $order = JONAH_ORDER_PUBLISHED)
    {
        return PEAR::raiseError(_("Tag support not enabled in backend."));
    }

    function getTagNames($ids)
    {
        return PEAR::raiseError(_("Tag support not enabled in backend."));
    }

    function getIdBySlug($channel)
    {
        return $this->_getIdBySlug($channel);
    }

}
