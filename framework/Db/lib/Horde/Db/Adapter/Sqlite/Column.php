<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Sqlite_Column extends Horde_Db_Adapter_Base_Column
{
    /**
     * @var array
     */
    protected static $_hasEmptyStringDefault = array('binary', 'string', 'text');


    public function extractDefault($default)
    {
        $default = parent::extractDefault($default);
        if ($this->isText()) {
            $default = $this->_unquote($default);
        }
        return $default;
    }


    /*##########################################################################
    # Type Juggling
    ##########################################################################*/

    public function stringToBinary($value)
    {
        return str_replace(array("\0", '%'), array('%00', '%25'), $value);
    }

    public function binaryToString($value)
    {
        return str_replace(array('%00', '%25'), array("\0", '%'), $value);
    }

    /**
     * @param   mixed  $value
     * @return  boolean
     */
    public function valueToBoolean($value)
    {
        if ($value == '"t"' || $value == "'t'") {
            return true;
        } elseif ($value == '""' || $value == "''") {
            return null;
        } else {
            return parent::valueToBoolean($value);
        }
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     * Unquote a string value
     *
     * @return string
     */
    protected function _unquote($string)
    {
        $first = substr($string, 0, 1);
        if ($first == "'" || $first == '"') {
            $string = substr($string, 1);
            if (substr($string, -1) == $first) {
                $string = substr($string, 0, -1);
            }
            $string = str_replace("$first$first", $first, $string);
        }

        return $string;
    }
}
