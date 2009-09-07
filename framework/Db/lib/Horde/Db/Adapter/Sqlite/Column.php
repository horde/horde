<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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

}
