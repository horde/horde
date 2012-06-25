<?php
/**
 * Ingo_Storage_Whitelist is the object used to hold whitelist rule
 * information.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
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
     * @param mixed $data  The list of addresses (array or string).
     *
     * @throws Ingo_Exception
     */
    public function setWhitelist($data)
    {
        global $conf;

        $addr = $this->_addressList($data);

        if (!empty($conf['storage']['maxwhitelist'])) {
            $addr_count = count($addr);
            if ($addr_count > $conf['storage']['maxwhitelist']) {
                throw new Ingo_Exception(sprintf(_("Maximum number of whitelisted addresses exceeded (Total addresses: %s, Maximum addresses: %s).  Could not add new addresses to whitelist."), $addr_count, $conf['storage']['maxwhitelist']));
            }
        }

        $this->_addr = $addr;
    }

    /**
     */
    public function getWhitelist()
    {
        return $this->_addr;
    }

}
