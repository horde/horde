<?php
/**
 * Ingo_Storage_Mock:: is used for testing purposes.  It just keeps the
 * data local and doesn't put it anywhere.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Ingo
 */

class Ingo_Storage_Mock extends Ingo_Storage
{
    /**
     */
    protected $_data = array();

    /**
     */
    protected function _retrieve($field)
    {
        if (empty($this->_data[$field])) {
            switch ($field) {
            case self::ACTION_BLACKLIST:
                return new Ingo_Storage_Blacklist();

            case self::ACTION_FILTERS:
                $ob = new Ingo_Storage_Filters();
                include INGO_BASE . '/config/prefs.php.dist';
                $ob->setFilterList(unserialize($_prefs['rules']['value']));
                return $ob;

            case self::ACTION_FORWARD:
                return new Ingo_Storage_Forward();

            case self::ACTION_VACATION:
                return new Ingo_Storage_VacationTest();

            case self::ACTION_WHITELIST:
                return new Ingo_Storage_Whitelist();

            case self::ACTION_SPAM:
                return new Ingo_Storage_Spam();

            default:
                return false;
            }
        }

        return $this->_data[$field];
    }

    /**
     */
    protected function _store(&$ob)
    {
        $this->_data[$ob->obType()] = $ob;
    }

}
