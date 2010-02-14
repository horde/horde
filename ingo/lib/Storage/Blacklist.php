<?php
/**
 * Ingo_Storage_Blacklist is the object used to hold blacklist rule
 * information.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Storage_Blacklist extends Ingo_Storage_Rule
{
    protected $_addr = array();
    protected $_folder = '';
    protected $_obtype = Ingo_Storage::ACTION_BLACKLIST;

    /**
     * Sets the list of blacklisted addresses.
     *
     * @param mixed $data    The list of addresses (array or string).
     * @param boolean $sort  Sort the list?
     *
     * @return boolean  True on success.
     * @throws Ingo_Exception
     */
    public function setBlacklist($data, $sort = true)
    {
        $addr = array_filter($this->_addressList($data, $sort), array('Ingo', 'filterEmptyAddress'));
        if (!empty($GLOBALS['conf']['storage']['maxblacklist'])) {
            $addr_count = count($addr);
            if ($addr_count > $GLOBALS['conf']['storage']['maxblacklist']) {
                throw new Ingo_Exception(sprintf(_("Maximum number of blacklisted addresses exceeded (Total addresses: %s, Maximum addresses: %s).  Could not add new addresses to blacklist."), $addr_count, $GLOBALS['conf']['storage']['maxblacklist']));
            }
        }

        $this->_addr = $addr;
        return true;
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
