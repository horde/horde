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
class Horde_Db_Adapter_Base_Index
{
    public $table;
    public $name;
    public $unique;
    public $primary;
    public $columns;


    /*##########################################################################
    # Construct/Destruct
    ##########################################################################*/

    /**
     * Construct
     *
     * @param   string  $table    The table the index is on
     * @param   string  $name     The index's name
     * @param   boolean $primary  Is this a primary key?
     * @param   boolean $unique   Is this a unique index?
     * @param   array   $columns  The columns this index covers
     */
    public function __construct($table, $name, $primary, $unique, $columns)
    {
        $this->table   = $table;
        $this->name    = $name;
        $this->primary = $primary;
        $this->unique  = $unique;
        $this->columns = $columns;
    }


    /*##########################################################################
    # Accessor
    ##########################################################################*/

    /**
     * @return  string
     */
    public function getName()
    {
        return $this->name;
    }


    /*##########################################################################
    # Casting
    ##########################################################################*/

    /**
     * Comma-separated list of the columns in the primary key
     *
     * @return string
     */
    public function __toString()
    {
        return implode(',', $this->columns);
    }

}
