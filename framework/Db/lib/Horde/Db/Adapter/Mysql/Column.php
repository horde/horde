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
class Horde_Db_Adapter_Mysql_Column extends Horde_Db_Adapter_Base_Column
{
    /**
     * @var array
     */
    protected static $_hasEmptyStringDefault = array('binary', 'string', 'text');

    /**
     * @var string
     */
    protected $_originalDefault = null;

    /**
     * Construct
     * @param   string  $name
     * @param   string  $default
     * @param   string  $sqlType
     * @param   boolean $null
     */
    public function __construct($name, $default, $sqlType=null, $null=true)
    {
        $this->_originalDefault = $default;
        parent::__construct($name, $default, $sqlType, $null);

        if ($this->_isMissingDefaultForgedAsEmptyString()) {
            $this->_default = null;
        }
    }

    /**
     * @param   string  $fieldType
     * @return  string
     */
    protected function _simplifiedType($fieldType)
    {
        if (strpos(strtolower($fieldType), 'tinyint(1)') !== false) {
            return 'boolean';
        } elseif (preg_match('/enum/i', $fieldType)) {
            return 'string';
        }
        return parent::_simplifiedType($fieldType);
    }

    /**
     * MySQL misreports NOT NULL column default when none is given.
     * We can't detect this for columns which may have a legitimate ''
     * default (string, text, binary) but we can for others (integer,
     * datetime, boolean, and the rest).
     *
     * Test whether the column has default '', is not null, and is not
     * a type allowing default ''.
     *
     * @return  boolean
     */
    protected function _isMissingDefaultForgedAsEmptyString()
    {
        return !$this->_null && $this->_originalDefault == '' &&
               !in_array($this->_type, self::$_hasEmptyStringDefault);
    }

}
