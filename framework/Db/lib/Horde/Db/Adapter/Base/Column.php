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
class Horde_Db_Adapter_Base_Column
{
    protected $_name;
    protected $_type;
    protected $_null;
    protected $_limit;
    protected $_precision;
    protected $_scale;
    protected $_unsigned;
    protected $_default;
    protected $_sqlType;
    protected $_isText;
    protected $_isNumber;


    /*##########################################################################
    # Construct/Destruct
    ##########################################################################*/

    /**
     * Construct
     *
     * @param   string  $name     The column's name, such as <tt>supplier_id</tt> in <tt>supplier_id int(11)</tt>.
     * @param   string  $default  The type-casted default value, such as +new+ in <tt>sales_stage varchar(20) default 'new'</tt>.
     * @param   string  $sqlType  Used to extract the column's length and signed status, if necessary. For example +60+ in <tt>company_name varchar(60)</tt>, or +unsigned => true+ in <tt>int(10) UNSIGNED</tt>.
     * @param   boolean $null     Determines if this column allows +NULL+ values.
     */
    public function __construct($name, $default, $sqlType = null, $null = true)
    {
        $this->_name      = $name;
        $this->_sqlType   = $sqlType;
        $this->_null      = $null;

        $this->_limit     = $this->_extractLimit($sqlType);
        $this->_precision = $this->_extractPrecision($sqlType);
        $this->_scale     = $this->_extractScale($sqlType);
        $this->_unsigned  = $this->_extractUnsigned($sqlType);

        $this->_type      = $this->_simplifiedType($sqlType);
        $this->_isText    = $this->_type == 'text'  || $this->_type == 'string';
        $this->_isNumber  = $this->_type == 'float' || $this->_type == 'integer' || $this->_type == 'decimal';

        $this->_default   = $this->extractDefault($default);
    }

    /**
     * @return  boolean
     */
    public function isText()
    {
        return $this->_isText;
    }

    /**
     * @return  boolean
     */
    public function isNumber()
    {
        return $this->_isNumber;
    }

    /**
     * Casts value (which is a String) to an appropriate instance.
     */
    public function typeCast($value)
    {
        if ($value === null) return null;

        switch ($this->_type) {
        case 'string':
        case 'text':
            return $value;
        case 'integer':
            return strlen($value) ? (int)$value : null;
        case 'float':
            return strlen($value) ? (float)$value : null;
        case 'decimal':
            return $this->valueToDecimal($value);
        case 'datetime':
        case 'timestamp':
            return $this->stringToTime($value);
        case 'time':
            return $this->stringToDummyTime($value);
        case 'date':
            return $this->stringToDate($value);
        case 'binary':
            return $this->binaryToString($value);
        case 'boolean':
            return $this->valueToBoolean($value);
        default:
            return $value;
        }
    }

    public function extractDefault($default)
    {
        return $this->typeCast($default);
    }


    /*##########################################################################
    # Accessor
    ##########################################################################*/

    /**
     * @return  string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @return  string
     */
    public function getDefault()
    {
        return $this->_default;
    }

    /**
     * @return  string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @return  int
     */
    public function getLimit()
    {
        return $this->_limit;
    }

    /**
     * @return  int
     */
    public function precision()
    {
        return $this->_precision;
    }

    /**
     * @return  int
     */
    public function scale()
    {
        return $this->_scale;
    }

    /**
     * @return  boolean
     */
    public function isUnsigned()
    {
        return $this->_unsigned;
    }

    /**
     * @return  boolean
     */
    public function isNull()
    {
        return $this->_null;
    }

    /**
     * @return  string
     */
    public function getSqlType()
    {
        return $this->_sqlType;
    }


    /*##########################################################################
    # Type Juggling
    ##########################################################################*/

    /**
     * Used to convert from Strings to BLOBs
     *
     * @return  string
     */
    public function stringToBinary($value)
    {
        return $value;
    }

    /**
     * Used to convert from BLOBs to Strings
     *
     * @return  string
     */
    public function binaryToString($value)
    {
        return $value;
    }

    /**
     * @param   string  $string
     * @return  Horde_Date
     */
    public function stringToDate($string)
    {
        if (empty($string) ||
            // preserve '0000-00-00' (http://bugs.php.net/bug.php?id=45647)
            preg_replace('/[^\d]/', '', $string) == 0) {
            return null;
        }

        $d = new Horde_Date($string);
        $d->setDefaultFormat('Y-m-d');

        return $d;
    }

    /**
     * @param   string  $string
     * @return  Horde_Date
     */
    public function stringToTime($string)
    {
        if (empty($string) ||
            // preserve '0000-00-00 00:00:00' (http://bugs.php.net/bug.php?id=45647)
            preg_replace('/[^\d]/', '', $string) == 0) {
            return null;
        }

        return new Horde_Date($string);
    }

    /**
     * @TODO Return a Horde_Date object instead?
     *
     * @param   string  $string
     * @return  Horde_Date
     */
    public function stringToDummyTime($value)
    {
        if (empty($string)) {
            return null;
        }
        return $this->stringToTime('2000-01-01 ' . $string);
    }

    /**
     * @param   mixed  $value
     * @return  boolean
     */
    public function valueToBoolean($value)
    {
        if ($value === true || $value === false) {
            return $value;
        }

        $value = strtolower($value);
        return $value == 'true' || $value == 't' || $value == '1';
    }

    /**
     * @param   mixed  $value
     * @return  decimal
     */
    public function valueToDecimal($value)
    {
        return (float)$value;
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     * @param   string  $sqlType
     * @return  int
     */
    protected function _extractLimit($sqlType)
    {
        if (preg_match("/\((.*)\)/", $sqlType, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    /**
     * @param   string  $sqlType
     * @return  int
     */
    protected function _extractPrecision($sqlType)
    {
        if (preg_match("/^(numeric|decimal|number)\((\d+)(,\d+)?\)/i", $sqlType, $matches)) {
            return (int)$matches[2];
        }
        return null;
    }

    /**
     * @param   string  $sqlType
     * @return  int
     */
    protected function _extractScale($sqlType)
    {
        switch (true) {
            case preg_match("/^(numeric|decimal|number)\((\d+)\)/i", $sqlType):
                return 0;
            case preg_match("/^(numeric|decimal|number)\((\d+)(,(\d+))\)/i",
                            $sqlType, $match):
                return (int)$match[4];
        }
    }

    /**
     * @param   string  $sqlType
     * @return  int
     */
    protected function _extractUnsigned($sqlType)
    {
        return (boolean)preg_match('/^int.*unsigned/i', $sqlType);
    }

    /**
     * @param   string  $fieldType
     * @return  string
     */
    protected function _simplifiedType($fieldType)
    {
        switch (true) {
        case preg_match('/int/i', $fieldType):
            return 'integer';
        case preg_match('/float|double/i', $fieldType):
            return 'float';
        case preg_match('/decimal|numeric|number/i', $fieldType):
            return $this->_scale == 0 ? 'integer' : 'decimal';
        case preg_match('/datetime/i', $fieldType):
            return 'datetime';
        case preg_match('/timestamp/i', $fieldType):
            return 'timestamp';
        case preg_match('/time/i', $fieldType):
            return 'time';
        case preg_match('/date/i', $fieldType):
            return 'date';
        case preg_match('/clob|text/i', $fieldType):
            return 'text';
        case preg_match('/blob|binary/i', $fieldType):
            return 'binary';
        case preg_match('/char|string/i', $fieldType):
            return 'string';
        case preg_match('/boolean/i', $fieldType):
            return 'boolean';
        }
    }
}
