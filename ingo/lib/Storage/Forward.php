<?php
/**
 * Ingo_Storage_Forward is the object used to hold mail forwarding rule
 * information.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Storage_Forward extends Ingo_Storage_Rule
{
    /**
     */
    protected $_addr = array();

    /**
     */
    protected $_keep = true;

    /**
     */
    protected $_obtype = Ingo_Storage::ACTION_FORWARD;

    /**
     */
    public function setForwardAddresses($data, $sort = true)
    {
        $this->_addr = $this->_addressList($data, $sort);
    }

    /**
     */
    public function setForwardKeep($data)
    {
        $this->_keep = $data;
    }

    /**
     */
    public function getForwardAddresses()
    {
        if (is_array($this->_addr)) {
            foreach ($this->_addr as $key => $val) {
                if (empty($val)) {
                    unset($this->_addr[$key]);
                }
            }
        }
        return $this->_addr;
    }

    /**
     */
    public function getForwardKeep()
    {
        return $this->_keep;
    }

}
