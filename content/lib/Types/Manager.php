<?php
/**
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
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

    public function __construct(Horde_Db_Adapter $db)
    {
        $this->_db = $db;
    }

    /**
     * Ensure that an array of types exist in storage. Create any that don't,
     * return type_ids for all.
     *
     * @param mixed $types  An array of types or single type value. Values typed
     *                      as an integer are assumed to already be an type_id.
     *
     * @return array  An array of type_ids.
     * @throws Content_Exception
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

        try {
            // Get the ids for any types that already exist.
            if (count($typeName)) {
                $rows = $this->_db->selectAssoc('SELECT type_id, type_name FROM '
                    . $this->_t('types') . ' WHERE type_name IN ('
                    . implode(',', array_map(array($this->_db, 'quoteString'), array_keys($typeName)))
                    . ')');
                foreach ($rows as $id => $type) {
                    $typeIndex = $typeName[$type];
                    unset($typeName[$type]);
                    $typeIds[$typeIndex] = (int)$id;
                }
            }

            // Create any types that didn't already exist
            foreach ($typeName as $type => $typeIndex) {
                $typeIds[$typeIndex] = $this->_db->insert(
                    'INSERT INTO ' . $this->_t('types')
                        . ' (type_name) VALUES ('
                        . $this->_db->quoteString($type) . ')');
            }
        } catch (Horde_Db_Exception $e) {
            throw new Content_Exception($e);
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
