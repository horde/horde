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
 * Concrete class for working with RSS items.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2005-2007 Zend Technologies USA Inc.
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Feed
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
