<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2016 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */

/**
 * This class represents the result set of a SELECT query from the MySQLi
 * driver.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Mysqli_Result extends Horde_Db_Adapter_Base_Result
{
    /**
     * Maps Horde_Db fetch mode constant to the extension constants.
     *
     * @var array
     */
    protected $_map = array(
        Horde_Db::FETCH_ASSOC => MYSQLI_ASSOC,
        Horde_Db::FETCH_NUM   => MYSQLI_NUM,
        Horde_Db::FETCH_BOTH  => MYSQLI_BOTH
    );

    /**
     * Returns a row from a resultset.
     *
     * @return array|boolean  The next row in the resultset or false if there
     *                        are no more results.
     */
    protected function _fetchArray()
    {
        return $this->_result->fetch_array($this->_map[$this->_fetchMode]);
    }

    /**
     * Returns the number of columns in the result set.
     *
     * @return integer  Number of columns.
     */
    protected function _columnCount()
    {
        return $this->_result->field_count;
    }
}
