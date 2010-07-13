<?php
/**
 * Jonah_News:: is the main class for handling news headlines for Jonah both
 * from internal Jonah generated news sources and external channels.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Jonah
 */
class Jonah_News
{
    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructs a new News storage object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
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
     * Checks if the channel is editable by first checking if the $channel_id
     * returns a valid channel array and then whether the channel type is
     * Jonah::INTERNAL_CHANNEL, which is the only one to allow stories to be
     * added.
     *
     * @param integer $channel_id  The channel id to check.
     *
     * @return array|PEAR_Error  The channel details as an array or a
     *                           PEAR_Error if not editable.
     */
    function isChannelEditable($channel_id)
    {
        /* Check if this channel id returns a valid channel. */
        $channel = $this->getChannel($channel_id);
        if (is_a($channel, 'PEAR_Error')) {
            return $channel;
        }

        /* Check if the channel type allows adding of stories. */
        if ($channel['channel_type'] != Jonah::INTERNAL_CHANNEL) {
            return PEAR::raiseError(sprintf(_("Feed \"%s\" is not authored on this system."), $channel['channel_name']));
        }

        return $channel;
    }

    /**
     * Returns the most recent or all stories from a channel.
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
     *                             Jonah::ORDER_* constants.
     *
     * @return array  The specified number (or less, if there are fewer) of
     *                stories from the given channel.
     */
    function getStories($channel_id, $max = 10, $from = 0, $refresh = false,
                        $date = null, $unreleased = false,
                        $order = Jonah::ORDER_PUBLISHED)
    {
        $channel = $this->getChannel($channel_id);
        if (is_a($channel, 'PEAR_Error')) {
            Horde::logMessage($channel, 'ERR');
            return array();
        }

        /* Fetch the stories according to channel type, using a
         * template method pattern for each type. */
        $funcs = array(
            Jonah::INTERNAL_CHANNEL => '_getInternalStories',
            Jonah::EXTERNAL_CHANNEL => '_getExternalStories',
            Jonah::AGGREGATED_CHANNEL => '_getAggregatedStories',
            Jonah::COMPOSITE_CHANNEL => '_getCompositeStories',
        );

        $func = $funcs[$channel['channel_type']];
        return $this->$func($channel, $max, $from, $refresh, $date, $unreleased, $order);
    }

    /**
     */
    function _getInternalStories($channel, $max = 10, $from = 0, $refresh = false,
                                 $date = null, $unreleased = false,
                                 $order = Jonah::ORDER_PUBLISHED)
    {
        return $GLOBALS['injector']->getInstance('Jonah_Driver')->legacyGetStories($channel, $max, $from, $refresh, $date, $unreleased, $order);
    }

    /**
     */
    function _getExternalStories($channel, $max = 10, $from = 0, $refresh = false,
                                 $date = null, $unreleased = false,
                                 $order = Jonah::ORDER_PUBLISHED)
    {
        if ($refresh) {
            $channel['channel_interval'] = -1;
        }
        $stories = $this->fetchExternalStories($channel['channel_id'], $channel['channel_url'], $channel['channel_interval']);
        array_walk($stories, array($this, '_escapeExternalStories'), $channel);
        if (!is_null($max)) {
            $stories = array_slice($stories, $from, $max);
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
    function _getAggregatedStories($channel, $max = 10, $from = 0, $refresh = false,
                                   $date = null, $unreleased = false,
                                   $order = Jonah::ORDER_PUBLISHED)
    {
        switch ($order) {
        case Jonah::ORDER_PUBLISHED:
            $sort = 'story_published';
            break;

        case Jonah::ORDER_READ:
            $sort = 'story_read';
            break;

        case Jonah::ORDER_COMMENTS:
            //@TODO
            break;
        }

        if ($refresh) {
            $channel['channel_interval'] = -1;
        }

        $stories = array();
        $date_format = $GLOBALS['prefs']->getValue('date_format');
        $channels = explode(':', $channel['channel_url']);
        foreach ($channels as $id) {
            $channel_data = $this->getChannel($id);
            if (is_a($channel_data, 'PEAR_Error')) {
                continue;
            }
            $externals = $this->fetchExternalStories(null, $channel_data['channel_url'], $channel['channel_interval']);
            if (!is_array($externals)) {
                continue;
            }

            foreach ($externals as $external) {
                $info = array();

                /* Check if we have seen this story already. */
                $story = $this->_getStoryByUrl($channel['channel_id'], $external['story_url']);
                if (is_array($story)) {
                    /* Check if the story is unchanged. */
                    if ($this->getChecksum($story) == $this->getChecksum($external)) {
                        $story['story_updated_date'] = strftime($date_format, $story['story_updated']);
                        $story['story_link'] = Horde::externalUrl($story['story_url']);
                        $stories[] = array_merge($channel_data, $story);
                        continue;
                    }

                    $info['story_id'] = $story['story_id'];
                }

                $info['channel_id'] = $channel['channel_id'];
                $info['story_title'] = $external['story_title'];
                $info['story_desc'] = $external['story_desc'];
                $info['story_url'] = $external['story_url'];
                $info['story_link'] = Horde::externalUrl($external['story_url']);
                $info['story_body'] = isset($external['story_body']) ? $external['story_body'] : null;
                $info['story_body_type'] = isset($external['story_body_type']) ? $external['story_body_type'] : 'text';
                $info['story_published'] = $external['story_published'];
                $info['story_updated'] = $external['story_updated'];

                $this->saveStory($info);

                $info['story_updated_date'] = strftime($date_format, $external['story_updated']);
                $stories[] = array_merge($channel_data, $info);
            }
        }

        Horde_Array::arraySort($stories, $sort, 1);
        if (!is_null($max)) {
            $stories = array_slice($stories, $from, $max);
        }

        return $stories;
    }

    /**
     */
    function _getCompositeStories($channel, $max = 10, $from = 0, $refresh = false,
                                  $date = null, $unreleased = false,
                                  $order = Jonah::ORDER_PUBLISHED)
    {
        switch ($order) {
        case Jonah::ORDER_PUBLISHED:
            $sort = 'story_published';
            break;

        case Jonah::ORDER_READ:
            $sort = 'story_read';
            break;

        case Jonah::ORDER_COMMENTS:
            //@TODO
            break;
        }

        $stories = array();
        $channels = explode(':', $channel['channel_url']);
        foreach ($channels as $subchannel) {
            $stories = array_merge($stories, $this->getStories($subchannel, null, 0, $refresh, $date));
        }
        foreach ($stories as $key => $story) {
            $stories[$key]['story_link'] = $this->getStoryLink($channel, $story);
        }
        Horde_Array::arraySort($stories, $sort, 1);
        if (!is_null($max)) {
            $stories = array_slice($stories, $from, $max);
        }
        return $stories;
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
            if ($channel['channel_type'] == Jonah::EXTERNAL_CHANNEL) {
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
     * Fetches a story list from an external channel, caching the data
     * and only actually fetching if there is no valid cache.
     *
     * @param integer $channel_id  The channel id to fetch.
     * @param string  $url         The url from where to fetch the story list.
     * @param integer $interval    The interval in seconds since the last fetch
     *                             after which the cache will be considered
     *                             expired and an actual fetch will be done.
     *
     * @return array  An array of available stories.
     */
    function fetchExternalStories($channel_id, $url, $interval)
    {
        $cache = $GLOBALS['injector']->getInstance('Horde_Cache');
        $timestamp = time();
        if (is_a($cache, 'Horde_Cache') && ($stories = $cache->get($url, $interval))) {
            $stories = Horde_Serialize::unserialize($stories, Horde_Serialize::UTF7_BASIC, $GLOBALS['registry']->getCharset());
        } else {
            $stories = Jonah_News::_fetchExternalStories($url, $timestamp);
            $cache->set($url, Horde_Serialize::serialize($stories, Horde_Serialize::UTF7_BASIC, $GLOBALS['registry']->getCharset()));
        }

        /* If the stories from cache return the same timestamp as
         * $timestamp it means that the cache has been refreshed. */
        if ($channel_id !== null) {
            if ($stories['timestamp'] == $timestamp) {
                $this->_timestampChannel($channel_id, $timestamp);
            }
        }

        unset($stories['timestamp']);
        return $stories;
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
     * Returns the available channel types based on what was set in the
     * configuration.
     *
     * @return array  The available news channel types.
     */
    function getAvailableTypes()
    {
        if (isset($types)) {
            return $types;
        }

        static $types = array();

        if (empty($GLOBALS['conf']['news']['enable'])) {
            return $types;
        }
        if (in_array('external', $GLOBALS['conf']['news']['enable'])) {
            $types[Jonah::EXTERNAL_CHANNEL] = _("External Feed");
        }
        if (in_array('internal', $GLOBALS['conf']['news']['enable'])) {
            $types[Jonah::INTERNAL_CHANNEL] = _("Local Feed");
        }
        if (in_array('aggregated', $GLOBALS['conf']['news']['enable'])) {
            $types[Jonah::AGGREGATED_CHANNEL] = _("Aggregated Feed");
        }
        if (in_array('composite', $GLOBALS['conf']['news']['enable'])) {
            $types[Jonah::COMPOSITE_CHANNEL] = _("Composite Feed");
        }

        return $types;
    }

    /**
     * Returns the default channel type based on what was set in the
     * configuration.
     *
     * @return integer  The default news channel type.
     */
    function getDefaultType()
    {
        if (in_array('external', $GLOBALS['conf']['news']['enable'])) {
            return Jonah::EXTERNAL_CHANNEL;
        } else {
            return Jonah::INTERNAL_CHANNEL;
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
     *                             Possible values are the Jonah::ORDER_*
     *                             constants.
     *
     * @return string  The rendered story listing.
     */
    function renderChannel($channel_id, $tpl, $max = 10, $from = 0, $order = Jonah::ORDER_PUBLISHED)
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
            $body_html = '<p>' . Horde_Text_Filter::filter($story['story_desc'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO, 'charset' => $GLOBALS['registry']->getCharset(), 'class' => null, 'callback' => null)) . "</p>\n" . $body_html;
            $body_text = Horde_String::wrap('  ' . $story['story_desc'], 70) . "\n\n" . $body_text;

            /* Add the text version of the story to the base message. */
            $message_text = new MIME_Part('text/plain');
            $message_text->setCharset($GLOBALS['registry']->getCharset());
            $message_text->setContents($message_text->replaceEOL($body_text));
            $message_text->setDescription(_("Plaintext Version of Story"));

            /* Add an HTML version of the story to the base message. */
            $message_html = new MIME_Part('text/html', Horde_String::wrap($body_html),
                                          $GLOBALS['registry']->getCharset(), 'inline');
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
            $message_text->setCharset($GLOBALS['registry']->getCharset());

            return $message_text;
        }
    }

    /**
     * Attempts to return a concrete Jonah_News instance based on $driver.
     *
     * @param string $driver  The type of concrete Jonah_News subclass to
     *                        return. The is based on the storage driver
     *                        ($driver). The code is dynamically included.
     *
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The newly created concrete Jonah_News instance, or false
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

        $class = 'Jonah_News_' . $driver;
        if (!class_exists($class, false)) {
            include dirname(__FILE__) . '/News/' . $driver . '.php';
        }
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return PEAR::raiseError(sprintf(_("No such backend \"%s\" found"), $driver));
        }
    }

    /**
     * Get the full body of an external feed story.
     *
     * @access private
     *
     * @param array $channel     The channel the story belongs to.
     * @param integer $story_id  The id of the story in the channel.
     *
     * @return array  The story array.
     */
    function _getExternalStory($channel, $story_id)
    {
        $stories = $this->fetchExternalStories($channel['channel_id'], $channel['channel_url'], $channel['channel_interval']);
        if (isset($stories[$story_id])) {
            return $stories[$story_id];
        }

        return PEAR::raiseError(sprintf(_("Story \"%s\" not found in \"%s\"."), $story_id, $channel['channel_title']));
    }

    /**
     * This function is called if cached data isn't available and is what
     * actually does the URL fetching and XML parsing to get the story list.
     * It is only called if the cached data has expired or didn't exist.
     *
     * @access private
     *
     * @param string  $url        The url from where to fetch the story list.
     * @param integer $timestamp  Timestamp of this fetch.
     *
     * @return array  An array of available stories.
     */
    function _fetchExternalStories($url, $timestamp)
    {
        try {
            $xml = Jonah::readURL($url);
        } catch (Jonah_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return array('timestamp' => $timestamp);
        }

        /* Parse the feed. */
        $charset = empty($xml['charset']) ? 'utf-8' : $xml['charset'];
        $parser = new Jonah_FeedParser($charset);
        if (!$parser->parse($xml['body'])) {
            if (isset($GLOBALS['notification'])) {
                $GLOBALS['notification']->push(sprintf(_("Error parsing external feed from %s: %s"), $url, $parser->error), 'warning');
            } else {
                Horde::logMessage(sprintf("Error parsing external feed from %s: %s", $url, $parser->error), 'ERR');
            }
        }
        $stories = $parser->structure;
        $parser->cleanup();

        /* Set the passed timestamp. */
        $items = array('timestamp' => $timestamp);
        if (!empty($stories['items'])) {
            foreach ($stories['items'] as $key => $story) {
                $items[$key]['story_id'] = $key;
                $items[$key]['story_title'] = isset($story['title']) ? Horde_String::convertCharset($story['title'], 'utf-8') : _("[No title]");
                $items[$key]['story_desc'] = isset($story['description']) ? Horde_String::convertCharset($story['description'], 'utf-8') : '';
                $items[$key]['story_url'] = isset($story['link']) ? $story['link'] : '';

                /* Set the body, and filter it if it's HTML */
                $items[$key]['story_body'] = isset($story['body']) ? Horde_String::convertCharset($story['body'], 'utf-8') : null;
                $items[$key]['story_body_type'] = isset($story['body_type']) ? Horde_String::convertCharset($story['body_type'], 'utf-8') : 'text';
                if ($items[$key]['story_body_type'] == 'html') {
                    $items[$key]['story_body'] = Horde_Text_Filter::filter($items[$key]['story_body'], 'xss');
                }

                if (isset($story['pubdate']) && $pubdate_dt = strtotime($story['pubdate'])) {
                    $items[$key]['story_published'] = $pubdate_dt;
                } else {
                    $items[$key]['story_published'] = $timestamp;
                }

                if (isset($story['moddate']) && $moddate_dt = strtotime($story['moddate'])) {
                    $items[$key]['story_updated'] = $moddate_dt;
                } else {
                    $items[$key]['story_updated'] = $timestamp;
                }

                // Media related
                if (isset($story['media:content']) && is_array($story['media:content'])) {
                    $items[$key]['story_media_content_url'] = $story['media:content']['url'];
                    $items[$key]['story_media_content_type'] = empty($story['media:content']['type']) ? null : $story['media:content']['type'];
                    $items[$key]['story_media_title'] = isset($story['media:title']) ? $story['media:title'] : '';
                    $items[$key]['story_media_description'] = isset($story['media:description']['value']) ? $story['media:description']['value'] : '';
                    $items[$key]['story_media_description_type'] = isset($story['media:description']['type']) ? $story['media:description']['type'] : '';
                    $items[$key]['story_media_thumbnail_url'] = (isset($story['media:thumbnail']['url']) ? $story['media:thumbnail']['url'] : (isset($story['media:content']['url']) ? $story['media:content']['url'] : ''));
                }
            }
        }

        return $items;
    }

    /**
     * Stubs for the tag functions. If supported by the backend, these need
     * to be implemented in the concrete Jonah_News_* class.
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
                            $order = Jonah::ORDER_PUBLISHED)
    {
        return PEAR::raiseError(_("Tag support not enabled in backend."));
    }

    function getTagNames($ids)
    {
        return PEAR::raiseError(_("Tag support not enabled in backend."));
    }


}
