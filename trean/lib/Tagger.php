<?php
/**
 * Trean interface to the Horde_Content tagger
 *
 * Copyright 2009-2013 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Trean
 */
class Trean_Tagger extends Horde_Core_Tagger
{
    protected $_app = 'trean';
    protected $_types = array('bookmark');

    /**
     * Searches for resources that are tagged with all of the requested tags.
     *
     * @param array $tags    Either a tag_id, tag_name or an array.
     * @param array $filter  Array of filter parameters.
     *                       - user (array) - only include objects owned by
     *                         these users.
     *
     * @return  A hash of 'bookmark' that contains an array of bookmark ids
     */
    public function search($tags, $filter = array())
    {
        $args = array();

        /* Add the tags to the search */
        $args['tagId'] = $GLOBALS['injector']
            ->getInstance('Content_Tagger')
            ->getTagIds($tags);

        $args['typeId'] = $this->_type_ids['bookmark'];
        $results = $GLOBALS['injector']
            ->getInstance('Content_Tagger')
            ->getObjects($args);

        $results = array_values($results);
        return $results;
    }

    /**
     * Returns tags on bookmarks belonging to the current user.
     *
     * @param string $token  The token to match the start of the tag with.
     *
     * @return A tag_id => tag_name hash
     * @throws Horde_Exception
     */
    public function listBookmarkTags()
    {
        try {
            return $GLOBALS['injector']->getInstance('Content_Tagger')
                ->getTags(array(
                    'typeId' => $this->_type_ids['bookmark'],
                    'userId' => $GLOBALS['registry']->getAuth())
                );
        } catch (Content_Exception $e) {
            throw new Horde_Exception($e);
        }
    }
}
