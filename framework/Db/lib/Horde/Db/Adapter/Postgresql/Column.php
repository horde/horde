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
     * @var array
     */
    protected static $_hasEmptyStringDefault = array('binary', 'string', 'text');


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
     * @param   string  $fieldType
     * @return  string
     */
    protected function _simplifiedType($fieldType)
    {
        switch (true) {
            // Numeric and monetary types
            case preg_match('/^(?:real|double precision)$/', $fieldType):
                return 'float';
            // Monetary types
            case preg_match('/^money$/', $fieldType):
                return 'decimal';
            // Character types
            case preg_match('/^(?:character varying|bpchar)(?:\(\d+\))?$/', $fieldType):
                return 'string';
            // Binary data types
            case preg_match('/^bytea$/', $fieldType):
                return 'binary';
            // Date/time types
            case preg_match('/^timestamp with(?:out)? time zone$/', $fieldType):
                return 'datetime';
            case preg_match('/^interval$/', $fieldType):
                return 'string';
            // Geometric types
            case preg_match('/^(?:point|line|lseg|box|"?path"?|polygon|circle)$/', $fieldType):
                return 'string';
            // Network address types
            case preg_match('/^(?:cidr|inet|macaddr)$/', $fieldType):
                return 'string';
            // Bit strings
            case preg_match('/^bit(?: varying)?(?:\(\d+\))?$/', $fieldType):
                return 'string';
            // XML type
            case preg_match('/^xml$/', $fieldType):
                return 'string';
            // Arrays
            case preg_match('/^\D+\[\]$/', $fieldType):
                return 'string';
            // Object identifier types
            case preg_match('/^oid$/', $fieldType):
                return 'integer';
        }

        // Pass through all types that are not specific to PostgreSQL.
        return parent::_simplifiedType($fieldType);
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
     * Used to convert from Strings to BLOBs (BYTEA).
     *
     * @return  string
     */
    public function stringToBinary($value)
    {
        /* MUST escape zero octet(0), single quote (39), and backslash (92).
         * MAY escape non-printable octets, but they are required in some
         * instances so it is best to escape all. */
        return preg_replace_callback("/[^\\x20-\\x26\\x28-\\x5b\\x5d-\\x73]/", array($this, 'stringToBinaryCallback'), $value);
    }

    /**
     * Callback function for stringToBinary().
     */
    public function stringToBinaryCallback($matches)
    {
        if ($matches[0] == "'") {
            return '\\\'';
        } elseif ($matches[0] == '\\') {
            return '\\\\\\\\';
        } else {
            return sprintf('\\\\%03.o', ord($matches[0]));
        }
    }

    /**
     * Used to convert from BLOBs (BYTEAs) to Strings.
     *
     * @return  string
     */
    public function binaryToString($value)
    {
        return preg_replace_callback("/[\\\'|\\\\\\\\|\\\\\d{3}]/", array($this, 'binaryToStringCallback'), $value);
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
        } else {
            return chr(octdec(substr($matches[0], -3)));
        }
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
