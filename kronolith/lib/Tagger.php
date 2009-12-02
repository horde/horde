<?php
/**
 * Kronolith interface to the Horde_Content tagger
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Kronolith
 */

// NOTE: Still need this here in addition to base.php to avoid having to fully
// initialize kronolith for each autocomplete ajax request.
Horde_Autoloader::addClassPattern('/^Content_/', $GLOBALS['registry']->get('fileroot', 'content') . '/lib/');
class Kronolith_Tagger
{
    /**
     * Local cache of the type name => ids from Content, so we don't have to
     * query for them each time.
     *
     * @var array
     */
    protected static $_type_ids = array();

    /**
     * @var Content_Tagger
     */
    protected static $_tagger;

    /**
     * Constructor - needs to instantiate the Content_Tagger object if it's not
     * already present.
     */
    public function __construct()
    {
        if (self::$_tagger) {
            return;
        }

        // Set up the context for the tagger and related content classes
        $GLOBALS['conf']['sql']['adapter'] = $GLOBALS['conf']['sql']['phptype'] == 'mysqli'
            ? 'mysqli'
            : 'pdo_' . $GLOBALS['conf']['sql']['phptype'];
        if (!empty($GLOBALS['conf']['sql']['params']['hostspec'])) {
            $GLOBALS['conf']['sql']['params']['host'] = $GLOBALS['conf']['sql']['params']['hostspec'];
        }

        $context = array('dbAdapter' => Horde_Db_Adapter::factory($GLOBALS['conf']['sql']));
        $user_mgr = new Content_Users_Manager($context);
        $type_mgr = new Content_Types_Manager($context);

        // Objects_Manager requires a Types_Manager
        $context['typeManager'] = $type_mgr;
        $object_mgr = new Content_Objects_Manager($context);

        // Create the Content_Tagger
        $context['userManager'] = $user_mgr;
        $context['objectManager'] = $object_mgr;

        // Cache the object statically
        self::$_tagger = new Content_Tagger($context);
        $types = $type_mgr->ensureTypes(array('calendar', 'event'));

        // Remember the types to avoid having Content query them again.
        self::$_type_ids = array('calendar' => (int)$types[0],
                                 'event' => (int)$types[1]);
    }

    /**
     * Tag a kronolith object with any number of tags.
     *
     * @param string $localId       The identifier of the kronolith object.
     * @param mixed $tags           Either a single tag string or an array of tags.
     * @param string $content_type  The type of object we are tagging (event/calendar).
     *
     * @return void
     */
    public function tag($localId, $tags, $content_type = 'event')
    {
        // If we don't have an array - split the string.
        if (!is_array($tags)) {
            $tags = self::$_tagger->splitTags($tags);
        }

        self::$_tagger->tag(Horde_Auth::getAuth(),
                   array('object' => $localId,
                         'type' => self::$_type_ids[$content_type]),
                   $tags);
    }

    /**
     * Retrieve the tags on given object(s).
     *
     * @param string $localId  The identifier of the kronolith object.
     * @param string $type     The type of object $localId represents.
     *
     * @return a tag_id => tag_name hash.
     */
    public function getTags($localId, $type = 'event')
    {
        if (!is_array($localId)) {
            $localId = array($localId);
        }
        $tags = array();
        foreach ($localId as $id) {
            $tags = $tags + self::$_tagger->getTags(array('objectId' => array('object' => $id, 'type' => $type)));
        }

        return $tags;
    }

    /**
     * Remove a tag from a kronolith object. Removes *all* tags - regardless of
     * the user that added the tag.
     *
     * @param string $localId       The kronolith object identifier.
     * @param mixed $tags           Either a tag_id, tag_name or an array of
     *                              ids or names to remove.
     * @param string $content_type  The type of object that $localId represents.
     *
     * @return void
     */
    public function untag($localId, $tags, $content_type = 'event')
    {
        self::$_tagger->removeTagFromObject(
            array('object' => $localId, 'type' => self::$_type_ids[$content_type]),
            $tags);
    }

    /**
     * Tag the given resource with *only* the tags provided, removing any tags
     * that are already present but not in the list.
     *
     * @param string $localId  The identifier for the kronolith object.
     * @param mixed $tags      Either a tag_id, tag_name, or array of tag_ids.
     * @param $content_type    The type of object that $localId represents.
     *
     * @return void
     */
    public function replaceTags($localId, $tags, $content_type = 'event')
    {
        // First get a list of existing tags.
        $existing_tags = $this->getTags($localId, $content_type);

        // If we don't have an array - split the string.
        if (!is_array($tags)) {
            $tags = self::$_tagger->splitTags($tags);
        }
        $remove = array();
        foreach ($existing_tags as $tag_id => $existing_tag) {
            $found = false;
            foreach ($tags as $tag_text) {
                //if ($existing_tag == Horde_String::lower($tag_text, true)) {
                if ($existing_tag == $tag_text) {
                    $found = true;
                    break;
                }
            }
            // Remove any tags that were not found in the passed in list.
            if (!$found) {
                $remove[] = $tag_id;
            }
        }

        $this->untag($localId, $remove, $content_type);
        $add = array();
        foreach ($tags as $tag_text) {
            $found = false;
            foreach ($existing_tags as $existing_tag) {
                if ($tag_text == $existing_tag) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $add[] = $tag_text;
            }
        }

        $this->tag($localId, $add, $content_type);
    }

    /**
     * Search for resources that are tagged with all of the requested tags.
     *
     * TODO: Change this to use something like a Content_Tagger::tagExists() or
     *       possibly add a $create = true parameter to ensureTags()
     *       so searching for any arbitrary text string won't cause the string
     *       to be added to the rampage_tags table as a tag (via ensureTags)
     *
     * @param array $tags  Either a tag_id, tag_name or an array.
     * @param array $filter  Array of filter parameters.
     *   (string)typeId      - only return either events or calendars, not both.
     *   (array)userId       - only include objects owned by userId(s).
     *   (array)calendarId   - restrict to events contained in these calendars.
     *
     * @return A hash of 'calendars' and 'events' that each contain an array
     *         of calendar_ids and event_uids respectively or PEAR_Error on
     *         failure. Should this return the objects?
     */
    public function search($tags, $filter = array())
    {
        if (!empty($filter['calendar'])) {
            // At least filter by ownerId to ease the post-filtering query.
            $owners = array();
            if (!is_array($filter['calendar'])) {
                $filter['calendar'] = array($filter['calendar']);
            }
            foreach ($filter['calendar'] as $calendar) {
                if ($GLOBALS['all_calendars'][$calendar]->get('owner')) {
                    $owners[] = $GLOBALS['all_calendars'][$calendar]->get('owner');
                }
            }
            $args = array('tagId' => self::$_tagger->ensureTags($tags),
                          'userId' => $owners,
                          'typeId' => self::$_type_ids['event']);

            // $results is an object_id => object_name hash
            $results = self::$_tagger->getObjects($args);

            //TODO: Are there any cases where we can shortcut the postFilter?
            $results = array('calendar' => array(),
                             'event' => $this->_postFilter($results, $filter['calendar']));
        } else {
            $args = array('tagId' => self::$_tagger->ensureTags($tags));
            if (!empty($filter['userId'])) {
                $args['userId'] = $filter['userId'];
            }

            $cal_results = array();
            if (empty($filter['typeId']) || $filter['typeId'] == 'calendar') {
                $args['typeId'] = self::$_type_ids['calendar'];
                $cal_results = self::$_tagger->getObjects($args);
            }

            $event_results = array();
            if (empty($filter['typeId']) || $filter['typeId'] == 'event') {
                $args['typeId'] = self::$_type_ids['event'];
                $event_results = self::$_tagger->getObjects($args);
            }

            $results = array('calendar' => array_values($cal_results),
                             'event' => array_values($event_results));
        }

        return $results;
    }

    /**
     * Filter events in the $results array to return only those that are
     * in $calendar.
     *
     * @param $results
     * @param $calendar
     * @return unknown_type
     */
    protected function _postFilter($results, $calendar)
    {
        $driver = Kronolith::getDriver();
        return $driver->filterEventsByCalendar($results, $calendar);
    }

    /**
     * List tags belonging to the current user beginning with $token.
     * Used for autocomplete code.
     *
     * @param string $token  The token to match the start of the tag with.
     *
     * @return A tag_id => tag_name hash
     */
    public function listTags($token)
    {
        return self::$_tagger->getTags(array('q' => $token, 'userId' => Horde_Auth::getAuth()));
    }

    /**
     * Return the data needed to build a tag cloud based on the passed in
     * user's tag data set.
     *
     * @param string $user    The user whose tags should be included.
     * @param integer $limit  The maximum number of tags to include.
     *
     * @return An array of hashes, each containing tag_id, tag_name, and count.
     */
    public function getCloud($user, $limit = 5)
    {
        return self::$_tagger->getTagCloud(array('userId' => $user,
                                                  'limit' => $limit));
    }
}
