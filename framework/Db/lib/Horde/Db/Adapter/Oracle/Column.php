<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */

/**
 * @since      Horde_Db 2.1.0
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Oracle_Column extends Horde_Db_Adapter_Base_Column
{
    /*##########################################################################
    # Construct/Destruct
    ##########################################################################*/

    /**
     * Constructor.
     *
     * @param string $name        Column name, such as "supplier_id" in
     *                            "supplier_id int(11)".
     * @param string $default     Type-casted default value, such as "new"
     *                            in "sales_stage varchar(20) default 'new'".
     * @param string $sqlType     Column type.
     * @param boolean $null       Whether this column allows NULL values.
     * @param integer $length     Column width.
     * @param integer $precision  Precision for NUMBER and FLOAT columns.
     * @param integer $scale      Number of digits to the right of the decimal
     *                            point in a number.
     */
    public function __construct($name, $default, $sqlType = null, $null = true,
                                $length = null, $precision = null,
                                $scale = null)
    {
        $this->_name      = $name;
        $this->_sqlType   = Horde_String::lower($sqlType);
        $this->_null      = $null;

        $this->_limit     = $length;
        $this->_precision = $precision;
        $this->_scale     = $scale;

        $this->_setSimplifiedType();
        $this->_isText    = $this->_type == 'text'  || $this->_type == 'string';
        $this->_isNumber  = $this->_type == 'float' || $this->_type == 'integer' || $this->_type == 'decimal';

        $this->_default   = $this->typeCast($default);
    }


    /*##########################################################################
    # Type Juggling
    ##########################################################################*/

    /**
     * Used to convert from BLOBs to Strings
     *
     * @return  string
     */
    public function binaryToString($value)
    {
        if (is_a($value, 'OCI-Lob')) {
            return $value->load();
        }
        return parent::binaryToString($value);
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     */
    protected function _setSimplifiedType()
    {
        if (Horde_String::lower($this->_sqlType) == 'number' &&
            $this->_precision == 1) {
            $this->_type = 'boolean';
            return;
        }
        parent::_setSimplifiedType();
    }
}
