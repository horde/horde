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
class Content_Objects_Manager
{
    /**
     * Database adapter
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Tables
     *
     * @TODO: this should probably be populated by the responsible manager...
     * @var array
     */
    protected $_tables = array(
        'objects' => 'rampage_objects',
    );

    /**
     * Type manager
     * @var Content_Types_Manager
     */
    protected $_typeManager;

    /**
     * Constructor
     */
    public function __construct($context = array())
    {
        if (!empty($context['dbAdapter'])) {
            $this->_db = $context['dbAdapter'];
        }

        if (!empty($context['typeManager'])) {
            $this->_typeManager = $context['typeManager'];
        }

        if (!empty($context['tables'])) {
            $this->_tables = array_merge($this->_tables, $context['tables']);
        }
    }

    /**
     * Check for object existence without causing the objects to be created.
     * Helps save queries for things like tags when we already know the object
     * doesn't yet exist in rampage tables.
     */
    public function exists($object, $type)
    {
        $type = array_pop($this->_typeManager->ensureTypes($type));
        $id = $this->_db->selectValue('SELECT object_id FROM ' . $this->_t('objects') . ' WHERE object_name = ' . $this->_db->quote($object) . ' AND type_id = ' . $type);
        if ($id) {
            return (int)$id;
        }

        return false;
    }

    /**
     * Ensure that an array of objects exist in storage. Create any that don't,
     * return object_ids for all. All objects in the $objects array must be
     * of the same content type.
     *
     * @param array $objects  An array of objects. Values typed as an integer
     *                        are assumed to already be an object_id.
     * @param mixed $type     Either a string type_name or integer type_id
     *
     * @return array  An array of object_ids.
     */
    public function ensureObjects($objects, $type)
    {
        if (!is_array($objects)) {
            $objects = array($objects);
        }

        $objectIds = array();
        $objectName = array();

        $type = array_pop($this->_typeManager->ensureTypes($type));

        // Anything already typed as an integer is assumed to be a object id.
        foreach ($objects as $objectIndex => $object) {
            if (is_int($object)) {
                $objectIds[$objectIndex] = $object;
            } else {
                $objectName[$object] = $objectIndex;
            }
        }

        // Get the ids for any objects that already exist.
        if (count($objectName)) {
            foreach ($this->_db->selectAll('SELECT object_id, object_name FROM ' . $this->_t('objects')
                     . ' WHERE object_name IN (' . implode(',', array_map(array($this->_db, 'quote'), array_keys($objectName)))
                     . ') AND type_id = ' . $type) as $row) {

                $objectIndex = $objectName[$row['object_name']];
                unset($objectName[$row['object_name']]);
                $objectIds[$objectIndex] = $row['object_id'];
            }
        }

        // Create any objects that didn't already exist
        foreach ($objectName as $object => $objectIndex) {
            $objectIds[$objectIndex] = $this->_db->insert('INSERT INTO ' . $this->_t('objects') . ' (object_name, type_id) VALUES (' . $this->_db->quote($object) . ', ' . $type . ')');
        }

        return $objectIds;
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
