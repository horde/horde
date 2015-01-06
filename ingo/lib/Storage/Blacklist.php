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
 * Ingo_Storage_Blacklist is the object used to hold blacklist rule
 * information.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Storage_Blacklist extends Ingo_Storage_Rule
{
    protected $_addr = array();
    protected $_folder = '';
    protected $_obtype = Ingo_Storage::ACTION_BLACKLIST;

    /**
     * Sets the list of blacklisted addresses.
     *
     * @param mixed $data  The list of addresses (array or string).
     *
     * @throws Ingo_Exception
     */
    public function setBlacklist($data)
    {
        global $injector;

        $addr = $this->_addressList($data);
        $max = $injector->getInstance('Horde_Core_Perms')->hasAppPermission(Ingo_Perms::getPerm('max_blacklist'));

        if (($max !== true) && !empty($max)) {
            $addr_count = count($addr);
            if ($addr_count > $max) {
                throw new Ingo_Exception(sprintf(_("Maximum number of blacklisted addresses exceeded (Total addresses: %s, Maximum addresses: %s).  Could not add new addresses to blacklist."), $addr_count, $max));
            }
        }

        $this->_addr = $addr;
    }

    /**
     */
    public function setBlacklistFolder($data)
    {
        $this->_folder = $data;
    }

    /**
     */
    public function getBlacklist()
    {
        return $this->_addr;
    }

    /**
     */
    public function getBlacklistFolder()
    {
        return $this->_folder;
    }

}
