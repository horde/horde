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
 *
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2013-2017 Horde LLC
 * @license    http://www.horde.org/licenses/bsd
 * @package    Db
 * @since      Horde_Db 2.1.0
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
        $array = oci_fetch_array(
            $this->_result, $this->_map[$this->_fetchMode] | OCI_RETURN_NULLS
        );
        if ($array) {
            $array = array_change_key_case($array, CASE_LOWER);
        }
        return $array;
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
