<?php
/**
 * Jonah external API interface.
 *
 * This file defines Jonah's external API interface. Other
 * applications can interact with Jonah through this API.
 *
 * @package Jonah
 */
class Jonah_Api extends Horde_Registry_Api
{
    /**
     * Get a list of stored channels.
     *
     * @param integer $type  The type of channel to filter for. Possible
     *                       values are either Jonah::INTERNAL_CHANNEL
     *                       to fetch only a list of internal channels,
     *                       or Jonah::EXTERNAL_CHANNEL for only external.
     *                       If null both channel types are returned.
     *
     * @return mixed         An array of channels or PEAR_Error on error.
     */
    public function listFeeds($type = null)
    {
        $news = Jonah_News::factory();
        $channels = $news->getChannels($type);

        return $channels;
    }

    /**
     * Return the requested stories
     *
     * @param int $channel_id   The channel to get the stories from.
     * @param int $max_stories  The maximum number of stories to get.
     * @param int $start_at     The story number to start retrieving.
     * @param int $order        How to order the results.
     *
     * @return An array of story information | PEAR_Error
     */
    public function stories($channel_id, $max_stories = 10, $start_at = 0,
                            $order = 0)
    {
        $news = Jonah_News::factory();
        $stories = $news->getStories($channel_id, $max_stories, $start_at, false,
                                     time(), false, $order);

        foreach (array_keys($stories) as $s) {
            if (empty($stories[$s]['story_body_type']) || $stories[$s]['story_body_type'] == 'text') {
                $stories[$s]['story_body_html'] = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($stories[$s]['story_body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
            } else {
                $stories[$s]['story_body_html'] = $stories[$s]['story_body'];
            }
        }

        return $stories;
    }

    /**
     * Fetches a story from a requested channel.
     *
     * @param integer $channel_id  The channel id to fetch.
     * @param integer $story_id    The story id to fetch.
     * @param boolean $read        Whether to update the read count.
     *
     * @return mixed  An array of story data | PEAR_Error
     */
    public function story($channel_id, $story_id, $read = true)
    {
        $news = Jonah_News::factory();
        $story = $news->getStory($channel_id, $story_id, $read);
        if (is_a($story, 'PEAR_Error')) {
            Horde::logMessage($story, 'ERR');
            return false;
        }
        if (empty($story['story_body_type']) || $story['story_body_type'] == 'text') {
            $story['story_body_html'] = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($story['story_body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        } else {
            $story['story_body_html'] = $story['story_body'];
        }

        return $story;
    }

    /**
     * Callback for comment API
     *
     * @param integer $id  Internal data identifier
     *
     * @return mixed  Name of object on success | false on failure
     */
    public function commentCallback($story_id)
    {
        if (!$GLOBALS['conf']['comments']['allow']) {
            return false;
        }

        $news = Jonah_News::factory();
        $story = $news->getStory(null, $story_id);
        if (is_a($story, 'PEAR_Error')) {
            return false;
        }

        return $story['story_title'];
    }

    /**
     * Check if comments are allowed.
     *
     * @return boolean
     */
    public function hasComments()
    {
        return $GLOBALS['conf']['comments']['allow'];
    }

    /**
     * Retrieve the list of used tag_names, tag_ids and the total number
     * of resources that are linked to that tag.
     *
     * @param array $tags  An optional array of tag_ids. If omitted, all tags
     *                     will be included.
     *
     * @param array $channel_id  An optional array of channel_ids.
     *
     * @return mixed  An array containing tag_name, and total | PEAR_Error
     */
    public function listTagInfo($tags = array(), $channel_id = null)
    {
        $news = Jonah_News::factory();
        return $news->listTagInfo($tags, $channel_id);
    }

    /**
     * Return a set of tag_ids, given the tag name
     *
     * @param array $names  An array of names to search for
     *
     * @return mixed  An array of tag_name => tag_ids | PEAR_Error
     */
    public function getTagIds($names)
    {
        $news = Jonah_News::factory();
        return $news->getTagIds($names);
    }

    /**
     * Searches internal channels for stories tagged with all requested tags.
     * Returns an application-agnostic array (useful for when doing a tag search
     * across multiple applications) containing the following keys:
     * <pre>
     *  'title'    - The title for this resource.
     *  'desc'     - A terse description of this resource.
     *  'view_url' - The URL to view this resource.
     *  'app'      - The Horde application this resource belongs to.
     * </pre>
     *
     * The 'raw' story array can be returned instead by setting $raw = true.
     *
     * @param array $names       An array of tag_names to search for (AND'd together).
     * @param integer $max       The maximum number of stories to return.
     * @param integer $from      The number of the story to start with.
     * @param array $channel_id  An array of channel_ids to limit the search to.
     * @param integer $order     How to order the results (a Jonah::ORDER_* constant)
     * @param boolean $raw       Return the raw story data?
     *
     * @return mixed  An array of results | PEAR_Error
     */
    public function searchTags($names, $max = 10, $from = 0, $channel_id = array(),
                               $order = 0, $raw = false)
    {
        global $registry;

        $news = Jonah_News::factory();
        $results = $news->searchTags($names, $max, $from, $channel_id, $order);
        if (is_a($results, 'PEAR_Error')) {
            return $results;
        }
        $return = array();
        if ($raw) {
            // Requesting the raw story information as returned from searchTags,
            // but add some additional information that external apps might
            // find useful.
            $comments = $GLOBALS['conf']['comments']['allow'] && $registry->hasMethod('forums/numMessages');
            foreach ($results as $story) {
                if (empty($story['story_body_type']) || $story['story_body_type'] == 'text') {
                    $story['story_body_html'] = $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($story['story_body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
                } else {
                    $story['story_body_html'] = $story['story_body'];
                }

                if ($comments) {
                    $story['num_comments'] = $registry->call('forums/numMessages',
                                                             array($story['story_id'],
                                                                   $registry->getApp()));
                }

                $return[$story['story_id']] = $story;
            }
        } else {
            foreach($results as $story) {
                if (!empty($story)) {
                    $return[] = array('title' => $story['story_title'],
                                                        'desc' => $story['story_desc'],
                                                        'view_url' => $story['story_link'],
                                                        'app' => 'jonah');
                }
            }
        }

        return $return;
    }

    /**
     * Get the count of stories in the specified channel
     *
     * @param int $channel_id
     * @return mixed  The story count
     */
    public function storyCount($channel_id)
    {
        global $registry;

        $results = $GLOBALS['injector']->getInstance('Jonah_Driver')->getStoryCount($channel_id);
        if (is_a($results, 'PEAR_Error')) {
            return 0;
        }

        return $results;
    }

}
