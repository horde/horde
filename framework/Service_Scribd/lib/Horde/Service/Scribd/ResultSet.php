<?php
/**
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Scribd
 */

/**
 * Scribd result set class
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Scribd
 */
class Horde_Service_Scribd_ResultSet extends Horde_Xml_Element_List
{
    /**
     * The classname for individual feed elements.
     * @var string
     */
    protected $_listItemClassName = 'Horde_Service_Scribd_Result';

    /**
     * Cache the individual list items so they don't need to be
     * searched for on every operation.
     */
    protected function _buildListItemCache()
    {
        $results = array();
        foreach ($this->_element->childNodes as $child) {
            if ($child->localName == 'result') {
                $results[] = $child;
            }
        }

        return $results;
    }

}
