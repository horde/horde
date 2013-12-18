<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2013 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Postgresql_Column extends Horde_Db_Adapter_Base_Column
{
    /*##########################################################################
    # Constants
    ##########################################################################*/

    /**
     * The internal PostgreSQL identifier of the money data type.
     * @const integer
     */
    const MONEY_COLUMN_TYPE_OID = 790;


    /**
     * @var integer
     */
    public static $moneyPrecision = 19;


    /**
     * Construct
     * @param   string  $name
     * @param   string  $default
     * @param   string  $sqlType
     * @param   boolean $null
     */
    public function __construct($name, $default, $sqlType=null, $null=true)
    {
        parent::__construct($name, $this->_extractValueFromDefault($default), $sqlType, $null);
    }

    /**
     */
    protected function _setSimplifiedType()
    {
        switch (true) {
        case preg_match('/^(?:real|double precision)$/', $this->_sqlType):
            // Numeric and monetary types
            $this->_type = 'float';
            return;
        case preg_match('/^money$/', $this->_sqlType):
            // Monetary types
            $this->_type = 'decimal';
            return;
        case preg_match('/^(?:character varying|bpchar)(?:\(\d+\))?$/', $this->_sqlType):
            // Character types
            $this->_type = 'string';
            return;
        case preg_match('/^bytea$/', $this->_sqlType):
            // Binary data types
            $this->_type = 'binary';
            return;
        case preg_match('/^timestamp with(?:out)? time zone$/', $this->_sqlType):
            // Date/time types
            $this->_type = 'datetime';
            return;
        case preg_match('/^interval$/', $this->_sqlType):
            $this->_type = 'string';
            return;
        case preg_match('/^(?:point|line|lseg|box|"?path"?|polygon|circle)$/', $this->_sqlType):
            // Geometric types
            $this->_type = 'string';
            return;
        case preg_match('/^(?:cidr|inet|macaddr)$/', $this->_sqlType):
            // Network address types
            $this->_type = 'string';
            return;
        case preg_match('/^bit(?: varying)?(?:\(\d+\))?$/', $this->_sqlType):
            // Bit strings
            $this->_type = 'string';
            return;
        case preg_match('/^xml$/', $this->_sqlType):
            // XML type
            $this->_type = 'string';
            return;
        case preg_match('/^\D+\[\]$/', $this->_sqlType):
            // Arrays
            $this->_type = 'string';
            return;
        case preg_match('/^oid$/', $this->_sqlType):
            // Object identifier types
            $this->_type = 'integer';
            return;
        }

        // Pass through all types that are not specific to PostgreSQL.
        parent::_setSimplifiedType();
    }

    /**
     * Extracts the value from a PostgreSQL column default definition.
     */
    protected function _extractValueFromDefault($default)
    {
        switch (true) {
            // Numeric types
            case preg_match('/\A-?\d+(\.\d*)?\z/', $default):
                return $default;
            // Character types
            case preg_match('/\A\'(.*)\'::(?:character varying|bpchar|text)\z/m', $default, $matches):
                return $matches[1];
            // Character types (8.1 formatting)
            case preg_match('/\AE\'(.*)\'::(?:character varying|bpchar|text)\z/m', $default, $matches):
                /*@TODO fix preg callback*/
                return preg_replace('/\\(\d\d\d)/', '$1.oct.chr', $matches[1]);
            // Binary data types
            case preg_match('/\A\'(.*)\'::bytea\z/m', $default, $matches):
                return $matches[1];
            // Date/time types
            case preg_match('/\A\'(.+)\'::(?:time(?:stamp)? with(?:out)? time zone|date)\z/', $default, $matches):
                return $matches[1];
            case preg_match('/\A\'(.*)\'::interval\z/', $default, $matches):
                return $matches[1];
            // Boolean type
            case $default == 'true':
                return true;
            case $default == 'false':
                return false;
            // Geometric types
            case preg_match('/\A\'(.*)\'::(?:point|line|lseg|box|"?path"?|polygon|circle)\z/', $default, $matches):
                return $matches[1];
            // Network address types
            case preg_match('/\A\'(.*)\'::(?:cidr|inet|macaddr)\z/', $default, $matches):
                return $matches[1];
            // Bit string types
            case preg_match('/\AB\'(.*)\'::"?bit(?: varying)?"?\z/', $default, $matches):
                return $matches[1];
            // XML type
            case preg_match('/\A\'(.*)\'::xml\z/m', $default, $matches):
                return $matches[1];
            // Arrays
            case preg_match('/\A\'(.*)\'::"?\D+"?\[\]\z/', $default, $matches):
                return $matches[1];
            // Object identifier types
            case preg_match('/\A-?\d+\z/', $default, $matches):
                return $matches[1];
            default:
                // Anything else is blank, some user type, or some function
                // and we can't know the value of that, so return nil.
                return null;
        }
    }

    /**
     * Used to convert from BLOBs (BYTEAs) to Strings.
     *
     * @return  string
     */
    public function binaryToString($value)
    {
        if (is_resource($value)) {
            $string = stream_get_contents($value);
            fclose($value);
            return $string;
        }

        return preg_replace_callback("/(?:\\\'|\\\\\\\\|\\\\\d{3})/", array($this, 'binaryToStringCallback'), $value);
    }

    /**
     * Callback function for binaryToString().
     */
    public function binaryToStringCallback($matches)
    {
        if ($matches[0] == '\\\'') {
            return "'";
        } elseif ($matches[0] == '\\\\\\\\') {
            return '\\';
        }

        return chr(octdec(substr($matches[0], -3)));
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
        if (preg_match('/^bigint/i', $sqlType)) {
            return 8;
        }
        if (preg_match('/^smallint/i', $sqlType)) {
            return 2;
        }
        return parent::_extractLimit($sqlType);
    }

    /**
     * @param   string  $sqlType
     * @return  int
     */
    protected function _extractPrecision($sqlType)
    {
        if (preg_match('/^money/', $sqlType)) {
            return self::$moneyPrecision;
        }
        return parent::_extractPrecision($sqlType);
    }

    /**
     * @param   string  $sqlType
     * @return  int
     */
    protected function _extractScale($sqlType)
    {
        if (preg_match('/^money/', $sqlType)) {
            return 2;
        }
        return parent::_extractScale($sqlType);
    }

}
