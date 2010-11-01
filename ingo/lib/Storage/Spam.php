<?php
/**
 * Ingo_Storage_Spam is an object used to hold default spam-rule filtering
 * information.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Ingo
 */
class Ingo_Storage_Spam extends Ingo_Storage_Rule
{
    /**
     * The object type.
     *
     * @var integer
     */
    protected $_obtype = Ingo_Storage::ACTION_SPAM;

    /**
     */
    protected $_folder = null;

    /**
     */
    protected $_level = 5;

    /**
     */
    public function setSpamFolder($folder)
    {
        $this->_folder = $folder;
    }

    /**
     */
    public function setSpamLevel($level)
    {
        $this->_level = $level;
    }

    /**
     */
    public function getSpamFolder()
    {
        return $this->_folder;
    }

    /**
     */
    public function getSpamLevel()
    {
        return $this->_level;
    }

}
