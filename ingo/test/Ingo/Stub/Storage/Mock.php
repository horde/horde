<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Driver used for testing purposes.
 *
 * @author   Jason M. Felice <jason.m.felice@gmail.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Stub_Storage_Mock extends Ingo_Storage
{
    /**
     */
    protected $_data = array();

    /**
     */
    protected function _retrieve($field, $readonly = false)
    {
        if (empty($this->_data[$field])) {
            switch ($field) {
            case self::ACTION_BLACKLIST:
                return new Ingo_Storage_Blacklist();

            case self::ACTION_FILTERS:
                return new Ingo_Storage_Filters_Memory();

            case self::ACTION_FORWARD:
                return new Ingo_Storage_Forward();

            case self::ACTION_VACATION:
                return new Ingo_Stub_Storage_Vacation();

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
    protected function _store($ob)
    {
        $this->_data[$ob->obType()] = $ob;
    }

    protected function _removeUserData($user)
    {
    }

}
