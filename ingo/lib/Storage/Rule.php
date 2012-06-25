<?php
/**
 * Ingo_Storage_Rule:: is the base class for the various action objects
 * used by Ingo_Storage.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
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
     * Whether the rule has been saved (if being saved separately).
     *
     * @var boolean
     */
    protected $_saved = false;

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
     * Marks the rule as saved or unsaved.
     *
     * @param boolean $data  Whether the rule has been saved.
     */
    public function setSaved($data)
    {
        $this->_saved = $data;
    }

    /**
     * Returns whether the rule has been saved.
     *
     * @return boolean  True if the rule has been saved.
     */
    public function isSaved()
    {
        return $this->_saved;
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
