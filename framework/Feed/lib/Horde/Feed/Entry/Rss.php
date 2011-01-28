<?php
/**
 * Portions Copyright 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @category Horde
 * @package  Horde_Feed
 */

/**
 * Concrete class for working with RSS items.
 *
 * @category Horde
 * @package  Horde_Feed
 */
class Horde_Feed_Entry_Rss extends Horde_Feed_Entry_Base
{
    /**
     * The XML string for an "empty" RSS entry.
     *
     * @var string
     */
    protected $_emptyXml = '<item/>';

    /**
     * Return encoded content if it's present.
     *
     * @return string
     */
    public function getContent()
    {
        if (isset($this->_children['content:encoded'])) {
            return $this->_children['content:encoded'];
        } elseif (isset($this->_children['encoded'])) {
            return $this->_children['encoded'];
        }
        return isset($this->_children['content']) ? $this->_children['content'] : array();
    }

}
