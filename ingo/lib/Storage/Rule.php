<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Ingo_Storage_Rule is the base class for the various action objects used by
 * Ingo_Storage.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Storage_Rule
{
    /**
     * The object type.
     *
     * @var integer
     */
    protected $_obtype;

    /**
     * Returns the object rule type.
     *
     * @return integer  The object rule type.
     */
    public function obType()
    {
        return $this->_obtype;
    }

    /**
     * Function to manage an internal address list.
     *
     * @param mixed $data  The incoming data (array or string).
     *
     * @return array  The address list.
     */
    protected function _addressList($data)
    {
        $ob = new Horde_Mail_Rfc822_List(is_array($data) ? $data : preg_split("/\s+/", $data));
        $ob->unique();

        return $ob->bare_addresses;
    }

}
