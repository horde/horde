<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
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
 * Ingo_Storage_Forward is the object used to hold mail forwarding rule
 * information.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
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
    public function setForwardAddresses($data)
    {
        $this->_addr = $this->_addressList($data);
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
