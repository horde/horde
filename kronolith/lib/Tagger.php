<?php
/**
 * Kronolith interface to the Horde_Content tagger
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Kronolith
 */
class Kronolith_Tagger extends Horde_Core_Tagger
{
    protected $_app = 'kronolith';
    protected $_types = array('event', 'calendar');

    /**
     * Searches for resources that are tagged with all of the requested tags.
     *
     * @param array $tags    Either a tag_id, tag_name or an array.
     * @param array $filter  Array of filter parameters.
     *                       - type (string) - only return either events or
     *                         calendars, not both.
     *                       - user (array) - only include objects owned by
     *                         these users.
     *                       - calendar (array) - restrict to events contained
     *                         in these calendars.
     *
     * @return  A hash of 'calendars' and 'events' that each contain an array
     *          of calendar_ids and event_uids respectively.
     */
    public function search($tags, $filter = array())
    {
        $args = array();

        /* These filters are mutually exclusive */
        if (array_key_exists('user', $filter)) {
            /* semi-hack to see if we are querying for a system-owned share -
             * will need to get the list of all system owned shares and query
             * using a calendar filter instead of a user filter. */
            if (empty($filter['user'])) {
                // @TODO: No way to get only the system shares the current
                // user can see?
                $calendars = $GLOBALS['injector']->getInstance('Kronolith_Shares')->listSystemShares();
                $args['calendarId'] = array();
                foreach ($calendars as $name => $share) {
                    if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
                        $args['calendarId'][] = $name;
                    }
                }
            } else {
                // Items owned by specific user(s)
                $args['userId'] = $filter['user'];
            }
        } elseif (!empty($filter['calendar'])) {
            // Only events located in specific calendar(s)
            if (!is_array($filter['calendar'])) {
                $filter['calendar'] = array($filter['calendar']);
            }
            $args['calendarId'] = $filter['calendar'];
        }

        /* Add the tags to the search */
        $args['tagId'] = $GLOBALS['injector']->getInstance('Content_Tagger')->getTagIds($tags);

        /* Restrict to events or calendars? */
        $cal_results = $event_results = array();
        if (empty($filter['type']) || $filter['type'] == 'calendar') {
            $args['typeId'] = $this->_type_ids['calendar'];
            $cal_results = $GLOBALS['injector']->getInstance('Content_Tagger')->getObjects($args);
        }

        if (empty($filter['type']) || $filter['type'] == 'event') {
            $args['typeId'] = $this->_type_ids['event'];
            $event_results = $GLOBALS['injector']->getInstance('Content_Tagger')->getObjects($args);
        }

        $results = array('calendars' => array_values($cal_results),
                         'events' => (!empty($args['calendarId']) && count($event_results))
                                     ? Kronolith::getDriver()->filterEventsByCalendar(array_values($event_results), $args['calendarId'])
                                     : array_values($event_results));

        return $results;
    }

}
