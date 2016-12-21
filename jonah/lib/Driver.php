<?php
/**
 * Jonah_Driver:: is responsible for storing, searching, sorting and filtering
 * locally generated and managed articles.  Aggregation is left to Hippo.
 *
 * Copyright 2002-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Ben Klang <ben@alkaloid.net>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
class Jonah_Driver
{
    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructs a new Driver storage object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Remove a channel from storage.
     *
     * @param array $info  A channel info array. (@TODO: Look at passing just
     *                     the id?)
     */
    public function deleteChannel($info)
    {
        return $this->_deleteChannel($info['channel_id']);
    }

    /**
     * Get a list of stored channels.
     *
     * @return array  An array of channel hashes.
     * @throws Jonah_Exception
     */
    public function getChannels()
    {
        return $this->_getChannels();
    }

    /**
     * Fetches the requested channel, while actually passing on the request to
     * the backend _getChannel() function to do the real work.
     *
     * @param integer $channel_id  The channel id to fetch.
     *
     * @return array  The channel details as an array
     * @throws InvalidArgumentException
     */
    public function getChannel($channel_id)
    {
        static $channel = array();

        /* We need a non empty channel id. */
        if (empty($channel_id)) {
            throw new InvalidArgumentException(_("Missing channel id."));
        }

        /* Cache the fetching of channels. */
        if (!isset($channel[$channel_id])) {
            $channel[$channel_id] = $this->_getChannel($channel_id);
            if (empty($channel[$channel_id]['channel_link'])) {
                $channel[$channel_id]['channel_official'] =
                    Horde::url('delivery/html.php', true, -1)->add('channel_id', $channel_id)->setRaw(false);
            } else {
                $channel[$channel_id]['channel_official'] = str_replace(array('%25c', '%c'), array('%c', $channel_id), $channel[$channel_id]['channel_link']);
            }

        }

        return $channel[$channel_id];
    }

    /**
     * Returns the most recent or all stories from a channel.
     *
     * @param integer $criteria    An associative array of attributes on which
     *                             the resulting stories should be filtered.
     *  Examples:
     *      'channel' => (string) Channel slug
     *      'channel_id' => (integer) Channel ID (Either an id or slug is required)
     *      'author' => (string) Story author
     *      'updated-min' => (Horde_Date) Only return stories updated on or
     *          after this date
     *      'updated-max' => (Horde_Date) Only return stories updatedon or
     *          before this date
     *      'published-min' => (Horde_Date) Only return stories published on or
     *          after this date
     *      'published-max' => (Horde_Date) Only return stories published on or
     *          before date
     *      'tags' => (array) Tag names that must match to be included
     *      'keywords' => (array) Strings which must match to be included
     *      'published' => (boolean) Whether to return only published stories:
     *          Possible values:
     *              null          return both
     *              'published'   returns publised
     *              'unpublished' returns unpublished
     *      'startnumber' => (integer) Story number to start at
     *      'limit' => (integer) Max number of stories
     * @param integer $order  How to order the results. A Jonah::ORDER_*
     *                        constant.
     *
     * @return array  The specified number (or less, if there are fewer) of
     *                stories from the given channel.
     * @throws InvalidArgumentException
     */
    public function getStories($criteria, $order = Jonah::ORDER_PUBLISHED)
    {
        // Convert a channel slug into a channel ID if necessary
        if (isset($criteria['channel']) && !isset($criteria['channel_id'])) {
            $criteria['channel_id'] = $this->getIdBySlug($criteria['channel']);
        }

        if (empty($criteria['channel_id'])) {
            throw InvalidArgumentException('Missing expected channel_id parameter.');
        }

        // Validate that we have proper Horde_Date objects
        if (isset($criteria['updated-min'])) {
            if (!is_a($criteria['updated-min'], 'Horde_Date')) {
                throw new InvalidArgumentException('Invalid date object provided for update start date.');
            }
        }
        if (isset($criteria['updated-max'])) {
            if (!is_a($criteria['updated-max'], 'Horde_Date')) {
                throw new InvalidArgumentException('Invalid date object provided for update end date.');
            }
        }
        if (isset($criteria['published-min'])) {
            if (!is_a($criteria['published-min'], 'Horde_Date')) {
                throw new InvalidArgumentException('Invalid date object provided for published start date.');
            }
        }
        if (isset($criteria['published-max'])) {
            if (!is_a($criteria['published-max'], 'Horde_Date')) {
                throw new InvalidArgumentException('Invalid date object provided for published end date.');
            }
        }

        if (!empty($criteria['tags'])) {
            $criteria['ids'] = $GLOBALS['injector']
                ->getInstance('Jonah_Tagger')
                ->search($criteria['tags'], array('channel_ids' => $criteria['channel_id']));
            unset($criteria['tags']);
        }

        return $this->_getStories($criteria, $order);
    }

    /**
     * Save the provided story to storage.
     *
     * @param array $info  The story information array. Passed by reference so
     *                     we can add/change the id when saved.
     */
    public function saveStory(&$info)
    {
        $this->_saveStory($info);
    }

    /**
     * Retrieve the requested story from storage.
     *
     * @param integer $story_id    The story id to obtain.
     * @param boolean $read        Increment the read counter?
     *
     * @return array  The story information array
     */
    public function getStory($story_id, $read = false)
    {
        $story = $this->_getStory($story_id, $read);
        $channel = $this->getChannel($story['channel_id']);

        /* Format story link. */
        $story['link'] = $this->getStoryLink($channel, $story);

        /* Format dates. */
        $date_format = $GLOBALS['prefs']->getValue('date_format');
        $story['updated_date'] = strftime($date_format, $story['updated']);
        if (!empty($story['published'])) {
            $story['published_date'] = strftime($date_format, $story['published']);
        }

        return $story;
    }

    /**
     * Returns the official link to a story.
     *
     * @param array $channel  A channel hash.
     * @param array $story    A story hash.
     *
     * @return Horde_Url  The story link.
     */
    public function getStoryLink($channel, $story)
    {
        if (!empty($story['url']) && empty($story['body'])) {
            $url = $story['url'];
        } elseif ((empty($story['url']) || !empty($story['body'])) &&
            !empty($channel['channel_story_url'])) {
            $url = $channel['channel_story_url'];
        } else {
            $url = Horde::url('stories/view.php', true, -1)->add(array('channel_id' => '%c', 'id' => '%s'))->setRaw(false);
        }

        return new Horde_Url(str_replace(array('%25c', '%25s', '%c', '%s'),
                                         array('%c', '%s', $channel['channel_id'], $story['id']),
                                         $url));
    }

    /**
     */
    public function getChecksum($story)
    {
        return md5($story['title'] . $story['description']);
    }

    /**
     */
    public function getIntervalLabel($seconds = null)
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
     *                             Possible values are the Jonah::ORDER_*
     *                             constants.
     *
     * @TODO: This doesn't belong in a storage driver class. Move it to a
     * view or possible a static method in Jonah::?
     *
     * @return string  The rendered story listing.
     */
    public function renderChannel($channel_id, $tpl, $max = 10, $from = 0, $order = Jonah::ORDER_PUBLISHED)
    {
        $channel = $this->getChannel($channel_id);

        $templates = Horde::loadConfiguration('templates.php', 'templates', 'jonah');
        $escape = !isset($templates[$tpl]['escape']) || !empty($templates[$tpl]['escape']);
        $template = new Horde_Template();

        if ($escape) {
            $channel['channel_name'] = htmlspecialchars($channel['channel_name']);
            $channel['channel_desc'] = htmlspecialchars($channel['channel_desc']);
        }
        $template->set('channel', $channel, true);

        /* Get one story more than requested to see if there are more stories. */
        if ($max !== null) {
            $stories = $this->getStories(
                    array('channel_id' => $channel_id,
                          'published' => true,
                          'startnumber' => $from,
                          'limit' => $max),
                    $order);
        } else {
            $stories = $this->getStories(array('channel_id' => $channel_id,
                                               'published' => true),
                                         $order);
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
     * @TODO: Move to a view class or static Jonah:: method?
     */
    protected function _escapeStories(&$value, $key)
    {
        $value['title'] = htmlspecialchars($value['title']);
        $value['description'] = htmlspecialchars($value['description']);
        if (isset($value['link'])) {
            $value['link'] = htmlspecialchars($value['link']);
        }
        if (empty($value['body_type']) || $value['body_type'] != 'richtext') {
            $value['body'] = htmlspecialchars($value['body']);
        }
    }

    /**
     * @TODO: Move to a view class or static Jonah:: method?
     */
    protected function _escapeStoryDescriptions(&$value, $key)
    {
        $value['description'] = nl2br($value['description']);
    }

    /**
     * Returns the provided story as a MIME part.
     *
     * @param array $story  A data array representing a story.
     *
     * @return MIME_Part  The MIME message part containing the story parts.
     * @TODO: Refactor to use new Horde MIME library
     */
    protected function getStoryAsMessage($story)
    {
        require_once 'Horde/MIME/Part.php';

        /* Add the story to the message based on the story's body type. */
        switch ($story['body_type']) {
        case 'richtext':
            /* Get a plain text version of a richtext story. */
            $body_html = $story['body'];
            $body_text = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($body_html, 'html2text');

            /* Add description. */
            $body_html = '<p>' . $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($story['desc'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO, 'callback' => null)) . "</p>\n" . $body_html;
            $body_text = Horde_String::wrap('  ' . $story['description'], 70) . "\n\n" . $body_text;

            /* Add the text version of the story to the base message. */
            $message_text = new MIME_Part('text/plain');
            $message_text->setCharset('UTF-8');
            $message_text->setContents($message_text->replaceEOL($body_text));
            $message_text->setDescription(_("Plaintext Version of Story"));

            /* Add an HTML version of the story to the base message. */
            $message_html = new MIME_Part('text/html', Horde_String::wrap($body_html),
                                          'UTF-8', 'inline');
            $message_html->setDescription(_("HTML Version of Story"));

            /* Add the two parts as multipart/alternative. */
            $basepart = new MIME_Part('multipart/alternative');
            $basepart->addPart($message_text);
            $basepart->addPart($message_html);

            return $basepart;

        case 'text':
            /* This is just a plain text story. */
            $message_text = new MIME_Part('text/plain');
            $message_text->setContents($message_text->replaceEOL($story['description'] . "\n\n" . $story['body']));
            $message_text->setCharset('UTF-8');

            return $message_text;
        }
    }

    /**
     * Return a list of story_ids contained in the specified
     * channel.
     *
     * @param integer $channel_id  The channel_id
     *
     * @return array  An array of story_ids.
     */
    public function getStoryIdsByChannel($channel_id)
    {
        return $this->_getStoryIdsByChannel($channel_id);
    }

    public function listTagInfo($channel_id = null)
    {
        global $injector;
var_dump($channel_id);
        // All channels
        if (!isset($channel_id)) {
            return $injector
                ->getInstance('Jonah_Tagger')
                ->getCloud(null, null);
        }

        // Limit by channel_id
        $story_ids = $this->_getStoryIdsByChannel($channel_id);
        return $injector
            ->getInstance('Jonah_Tagger')
            ->getTagCountsByObjects($story_ids, Jonah_Tagger::TYPE_STORY);
    }

    public function getIdBySlug($channel)
    {
        return $this->_getIdBySlug($channel);
    }

}
