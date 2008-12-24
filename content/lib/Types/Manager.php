<?php
/**
 * Copyright 2008 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Content
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Content
 */
class Content_Types_Manager
{
    /**
     * Database adapter
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Tables
     * @var array
     */
    protected $_tables = array(
        'types' => 'rampage_types',
    );

    public function __construct($context = array())
    {
        $this->_db = $adapter;
    }

    /**
     * Set the database adapter.
     *
     * @param Horde_Db $db  The database connection
     */
    public function setDbAdapter($db)
    {
        $this->_db = $db;
    }

    /**
     * Change the name of a database table.
     *
     * @param string $tableType
     * @param string $tableName
     */
    public function setTableName($tableType, $tableName)
    {
        $this->_tables[$tableType] = $tableName;
    }

    /**
     * Ensure that an array of types exist in storage. Create any that don't,
     * return type_ids for all.
     *
     * @param array $types  An array of types. Values typed as an integer
     *                        are assumed to already be an type_id.
     *
     * @return array  An array of type_ids.
     */
    public function ensureTypes($types)
    {
        if (!is_array($types)) {
            $types = array($types);
        }

        $typeIds = array();
        $typeName = array();

        // Anything already typed as an integer is assumed to be a type id.
        foreach ($types as $typeIndex => $type) {
            if (is_int($type)) {
                $typeIds[$typeIndex] = $type;
            } else {
                $typeName[$type] = $typeIndex;
            }
        }

        // Get the ids for any types that already exist.
        if (count($typeName)) {
            foreach ($this->_db->selectAll('SELECT type_id, type_name FROM ' . $this->_t('types') . ' WHERE type_name IN ('.implode(',', array_map(array($this->_db, 'quote'), array_keys($typeName))).')') as $row) {
                $typeIndex = $typeName[$row['type_name']];
                unset($typeName[$row['type_name']]);
                $typeIds[$typeIndex] = (int)$row['type_id'];
            }
        }

        // Create any types that didn't already exist
        foreach ($typeName as $type => $typeIndex) {
            $typeIds[$typeIndex] = $this->_db->insert('INSERT INTO ' . $this->_t('types') . ' (type_name) VALUES (' . $this->_db->quote($type) . ')');
        }

        return $typeIds;
    }

    /**
     * Shortcut for getting a table name.
     *
     * @param string $tableType
     *
     * @return string  Configured table name.
     */
    protected function _t($tableType)
    {
        return $this->_db->quoteTableName($this->_tables[$tableType]);
    }

}
