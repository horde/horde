<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Oracle_Result extends Horde_Db_Adapter_Base_Result
{
    /**
     * Maps Horde_Db fetch mode constant to the extension constants.
     *
     * @var array
     */
    protected $_map = array(
        Horde_Db::FETCH_ASSOC => OCI_ASSOC,
        Horde_Db::FETCH_NUM   => OCI_NUM,
        Horde_Db::FETCH_BOTH  => OCI_BOTH
    );

    /**
     * Returns a row from a resultset.
     *
     * @return array|boolean  The next row in the resultset or false if there
     *                        are no more results.
     */
    protected function _fetchArray()
    {
        return array_change_key_case(
            oci_fetch_array(
                $this->_result,
                $this->_map[$this->_fetchMode]
            ),
            CASE_LOWER
        );
    }

    /**
     * Returns the number of columns in the result set.
     *
     * @return integer  Number of columns.
     */
    protected function _columnCount()
    {
        return oci_num_fields($this->_result);
    }
}
