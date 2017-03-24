<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd
 * @package    Db
 * @subpackage Adapter
 */

/**
 * This class represents the result set of a SELECT query from the PDO drivers.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2013-2017 Horde LLC
 * @license    http://www.horde.org/licenses/bsd
 * @package    Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Pdo_Result extends Horde_Db_Adapter_Base_Result
{
    /**
     * Maps Horde_Db fetch mode constant to the extension constants.
     *
     * @var array
     */
    protected $_map = array(
        Horde_Db::FETCH_ASSOC => PDO::FETCH_ASSOC,
        Horde_Db::FETCH_NUM   => PDO::FETCH_NUM,
        Horde_Db::FETCH_BOTH  => PDO::FETCH_BOTH
    );

    /**
     * Returns a row from a resultset.
     *
     * @return array|boolean  The next row in the resultset or false if there
     *                        are no more results.
     */
    protected function _fetchArray()
    {
        try {
            return $this->_result->fetch($this->_map[$this->_fetchMode]);
        } catch (PDOException $e) {
            throw new Horde_Db_Exception($e);
        }
    }

    /**
     * Returns the number of columns in the result set.
     *
     * @return integer  Number of columns.
     */
    protected function _columnCount()
    {
        try {
            return $this->_result->columnCount();
        } catch (PDOException $e) {
            throw new Horde_Db_Exception($e);
        }
    }
}
