<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * A rule that deals with an address list.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Storage_Rule_Addresses
extends Ingo_Storage_Rule
{
    /**
     * Address list.
     *
     * @var array
     */
    protected $_addr;

    /**
     * Function to manage an internal address list.
     *
     * @param mixed $data  The incoming data (array or string).
     *
     * @return array  The address list.
     */
    protected function _addressList($data)
    {
        $ob = new Horde_Mail_Rfc822_List(
            is_array($data) ? $data : preg_split("/\s+/", $data)
        );
        $ob->unique();

        return $ob->bare_addresses;
    }

}
