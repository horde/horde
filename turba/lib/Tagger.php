<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */

/**
 * Interface to the Horde_Content tagger.
 *
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */
class Turba_Tagger extends Horde_Core_Tagger
{
    /**
     */
    protected $_app = 'turba';

    /**
     */
    protected $_types = array('contact');

    /**
     * Searches for resources that are tagged with all of the requested tags.
     *
     * @param array $tags    Either a tag_id, tag_name or an array.
     * @param array $filter  Array of filter parameters:
     * <pre>
     *   - user: (array) Only include objects owned by these users.
     *   - list: (array) Restrict to contacts contained in these address
     *           books.
     * </pre>
     *
     * @return array  A hash of results.
     */
    public function search($tags, $filter = array())
    {
        global $injector;

        $args = array();
        $tagger = $injector->getInstance('Content_Tagger');

        // These filters are mutually exclusive
        if (array_key_exists('user', $filter)) {
            // Items owned by specific user(s)
            $args['userId'] = $filter['user'];
        } elseif (!empty($filter['list'])) {
            // Only events located in specific address book(s)
            if (!is_array($filter['list'])) {
                $filter['list'] = array($filter['list']);
            }
            $args['listId'] = $filter['list'];
        }

        // Add the tags to the search
        $args['tagId'] = $tagger->ensureTags($tags);
        $args['typeId'] = $this->_type_ids['contact'];

        return array_values($tagger->getObjects($args));
    }

}
