<?php
/**
 * Jonah interface to the Horde_Content tagger
 *
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 */
class Jonah_Tagger extends Horde_Core_Tagger
{
    const TYPE_STORY = 'story';

    protected $_app = 'jonah';
    protected $_types = array(self::TYPE_STORY);

    /**
     * Searches for resources that are tagged with all of the requested tags.
     *
     * @param array $tags    Either a tag_id, tag_name or an array.

     *
     * @return  array  An array of story ids matching the search criteria.
     */
    public function search($tags, $filter = array())
    {
        $args = array('typeId' => $this->_type_ids[self::TYPE_STORY]);

        /* Add the tags to the search */
        $args['tagId'] = $GLOBALS['injector']
            ->getInstance('Content_Tagger')
            ->getTagIds($tags);

        if (!empty($filter['channel_ids'])) {
            $channels = $filter['channel_ids'];
        } else {
            $channels = array();
        }

        return array_values($GLOBALS['injector']
            ->getInstance('Content_Tagger')
            ->getObjects($args)
        );
    }

}
