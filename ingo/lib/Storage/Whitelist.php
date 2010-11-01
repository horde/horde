<?php
/**
 * Ingo_Storage_Whitelist is the object used to hold whitelist rule
 * information.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Storage_Whitelist extends Ingo_Storage_Rule
{
    /**
     */
    protected $_addr = array();

    /**
     */
    protected $_obtype = Ingo_Storage::ACTION_WHITELIST;

    /**
     * Sets the list of whitelisted addresses.
     *
     * @param mixed $data    The list of addresses (array or string).
     * @param boolean $sort  Sort the list?
     *
     * @return boolean  True on success.
     * @throws Ingo_Exception
     */
    public function setWhitelist($data, $sort = true)
    {
        $addr = array_filter($this->_addressList($data, $sort), array('Ingo', 'filterEmptyAddress'));
        if (!empty($GLOBALS['conf']['storage']['maxwhitelist'])) {
            $addr_count = count($addr);
            if ($addr_count > $GLOBALS['conf']['storage']['maxwhitelist']) {
                throw new Ingo_Exception(sprintf(_("Maximum number of whitelisted addresses exceeded (Total addresses: %s, Maximum addresses: %s).  Could not add new addresses to whitelist."), $addr_count, $GLOBALS['conf']['storage']['maxwhitelist']));
            }
        }

        $this->_addr = $addr;
        return true;
    }

    /**
     */
    public function getWhitelist()
    {
        return $this->_addr;
    }

}
