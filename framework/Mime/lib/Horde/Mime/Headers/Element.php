<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 */

/**
 * This class represents a single header element.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 * @since     2.5.0
 *
 * @property-read string $name  Header name.
 * @property-read string $value_single  The first header value.
 */
abstract class Horde_Mime_Headers_Element
implements IteratorAggregate
{
    /**
     * Header name (UTF-8, although limited to US-ASCII subset by RFCs).
     *
     * @var string
     */
    protected $_name;

    /**
     * Header values.
     *
     * @var array
     */
    protected $_values = array();

    /**
     * Constructor.
     *
     * @param string $name  Header name.
     * @param mixed $value  Header value(s).
     */
    public function __construct($name, $value)
    {
        $this->_name = trim($name);
        $this->setValue($value);
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'name':
            return $this->_name;

        case 'value_single':
            return reset($this->_values);
        }
    }

    /**
     * Set the value of the header.
     *
     * @param mixed $value  Header value(s).
     */
    final public function setValue($value)
    {
        $this->_setValue($value);
    }

    /**
     * TODO
     */
    abstract protected function _setValue($value);

    /**
     * Returns the encoded string value(s) needed when sending the header text
     * to a RFC compliant mail submission server.
     *
     * @param array $opts  Additional options:
     *   - charset: (string) Charset to encode to.
     *              DEFAULT: UTF-8
     *
     * @return array  An array of string values.
     */
    final public function sendEncode(array $opts = array())
    {
        return $this->_sendEncode(array_merge(array(
            'charset' => 'UTF-8'
        ), $opts));
    }

    /**
     * TODO
     */
    protected function _sendEncode($opts)
    {
        return $this->_values;
    }

    /* Static methods */

    /**
     * Return list of explicit header names handled by this driver.
     *
     * @return array  Header list.
     */
    public static function getHandles()
    {
        return array();
    }

    /* IteratorAggregate method */

    /**
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_values);
    }

}
