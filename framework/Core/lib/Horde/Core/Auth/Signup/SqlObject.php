<?php
/**
 * SQL storage object for auth signup information.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Duck <duck@obala.net>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Core
 */
class Horde_Core_Auth_Signup_SqlObject
{
    /**
     * Key-value hash that will be serialized.
     *
     * @see getData()
     * @var array
     */
    protected $_data = array();

    /**
     * The unique name of this object. These names have the same requirements
     * as other object names - they must be unique, etc.
     *
     * @var string
     */
    protected $_name;

    /**
     * Constructor.
     *
     * @param string $id  The id of the signup.
     */
    public function __construct($id)
    {
        $this->_name = $id;
    }

    /**
     * Gets the data array.
     *
     * @return array  The internal data array.
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Sets the data array.
     *
     * @param array $data  The data array to store internally.
     */
    public function setData($data)
    {
        $part = unserialize($data['signup_data']);
        if (!empty($part) && is_array($part)) {
            if (!empty($part['extra'])) {
                $extra = $part['extra'];
                unset($part['extra']);
                $part = array_merge($part, $extra);
            }
            $this->_data = array_merge($data, $part);
        } else {
            $this->_data = $data;
        }

        unset($this->_data['signup_data']);

        if (isset($data['signup_date'])) {
            $this->_data['dateReceived'] = $data['signup_date'];
        }
    }

    /**
     * Gets the name of this object.
     *
     * @return string The object name.
     */
    public function getName()
    {
        return $this->_name;
    }
}
