<?php
/**
 * Ingo_Storage_mock:: is used for testing purposes.  It just keeps the
 * data local and doesn't put it anywhere.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Ingo
 */

class Ingo_Storage_mock extends Ingo_Storage
{
    protected $_data = array();

    protected function &_retrieve($field)
    {
        if (empty($this->_data[$field])) {
            switch ($field) {
            case self::ACTION_BLACKLIST:
                return new Ingo_Storage_blacklist();

            case self::ACTION_FILTERS:
                $ob = new Ingo_Storage_filters();
                include INGO_BASE . '/config/prefs.php.dist';
                $ob->setFilterList(unserialize($_prefs['rules']['value']));
                return $ob;

            case self::ACTION_FORWARD:
                return new Ingo_Storage_forward();

            case self::ACTION_VACATION:
                return new Ingo_Storage_vacation();

            case self::ACTION_WHITELIST:
                return new Ingo_Storage_whitelist();

            case self::ACTION_SPAM:
                return new Ingo_Storage_spam();

            default:
                return false;
            }
        }

        return $this->_data[$field];
    }

    protected function _store(&$ob)
    {
        $this->_data[$ob->obType()] = $ob;
    }

}
