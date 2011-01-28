<?php
/**
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Feed
 */

/**
 * Blogroll feed list class
 *
 * This is not a generic OPML implementation, but one focused on lists of feeds,
 * i.e. blogrolls. See http://en.wikipedia.org/wiki/OPML for more information on
 * OPML.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Feed
 */
class Horde_Feed_Blogroll extends Horde_Feed_Base
{
    /**
     * The classname for individual feed elements.
     * @var string
     */
    protected $_listItemClassName = 'Horde_Feed_Entry_Blogroll';

    /**
     * The default namespace for blogrolls.
     * @var string
     */
    protected $_defaultNamespace = '';

    /**
     * The XML string for an "empty" Blogroll.
     * @var string
     */
    protected $_emptyXml = '<?xml version="1.0" encoding="utf-8"?><opml version="1.1"></opml>';

    /**
     * Cache outline elements so they don't need to be searched for on every
     * operation.
     */
    protected function _buildListItemCache()
    {
        $entries = array();
        foreach ($this->_element->getElementsByTagName('outline') as $child) {
            if ($child->attributes->getNamedItem('xmlUrl')) {
                $entries[] = $child;
            }
        }

        return $entries;
    }

    public function getBody()
    {
        return $this;
    }

    public function getOutline()
    {
        return $this;
    }

    public function getTitle()
    {
        return $this->head->title;
    }

}
