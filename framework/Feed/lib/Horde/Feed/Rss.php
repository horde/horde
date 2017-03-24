<?php
/**
 * Portions Copyright 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * Copyright 2007-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Feed
 */

/**
 * RSS channel class
 *
 * The Horde_Feed_Rss class is a concrete subclass of Horde_Feed_Base
 * meant for representing RSS channels. It does not add any methods to
 * its parent, just provides a classname to check against with the
 * instanceof operator, and expects to be handling RSS-formatted data
 * instead of Atom.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2005-2007 Zend Technologies USA Inc.
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Feed
 */
class Horde_Feed_Rss extends Horde_Feed_Base
{
    /**
     * The classname for individual channel elements.
     * @var string
     */
    protected $_listItemClassName = 'Horde_Feed_Entry_Rss';

    /**
     * The default namespace for RSS channels.
     * @var string
     */
    protected $_defaultNamespace = 'rss';

    /**
     * The XML string for an "empty" RSS feed.
     * @var string
     */
    protected $_emptyXml = '<?xml version="1.0" encoding="utf-8"?><rss version="2.0"><channel></channel></rss>';

    /**
     * Cache the individual feed elements so they don't need to be searched for
     * on every operation.
     * @return array
     */
    protected function _buildListItemCache()
    {
        $items = array();
        foreach ($this->_element->childNodes as $child) {
            if ($child->localName == 'item') {
                $items[] = $child;
            }
        }

        // Brute-force search for <item> elements if we haven't found any so
        // far.
        if (!count($items)) {
            foreach ($this->_element->ownerDocument->getElementsByTagName('item') as $child) {
                $items[] = $child;
            }
        }

        return $items;
    }

}
