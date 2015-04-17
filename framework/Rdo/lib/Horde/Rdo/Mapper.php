<?php
/**
 * Rdo Mapper base class.
 *
 * @category Horde
 * @package  Rdo
 */

/**
 * Rdo Mapper class. Controls mapping of entity obects (instances of
 * Horde_Rdo_Base) from and to Horde_Db_Adapters.
 *
 * Public properties:
 *   $adapter - Horde_Db_Adapter that stores this Mapper's objects.
 *
 *   $inflector - The Horde_Support_Inflector this mapper uses to singularize
 *   and pluralize PHP class, database table, and database field/key names.
 *
 *   $table - The Horde_Db_Adapter_Base_TableDefinition object describing
 *   the main table of this entity.
 *
 * @category Horde
 * @package  Rdo
 */
abstract class Horde_Rdo_Mapper implements Countable
{
    /**
     * If this is true and fields named created_at and updated_at are present,
     * Rdo will automatically set creation and last updated timestamps.
     * Timestamps are always GMT for portability.
     *
     * @var boolean
     */
    protected $_setTimestamps = true;

    /**
     * What class should this Mapper create for objects? Defaults to the Mapper
     * subclass' name minus "Mapper". So if the Rdo_Mapper subclass is
     * UserMapper, it will default to trying to create User objects.
     *
     * @var string
     */
    protected $_classname;

    /**
     * The definition of the database table (or view, etc.) that holds this
     * Mapper's objects.
     *
     * @var Horde_Db_Adapter_Base_TableDefinition
     */
    protected $_tableDefinition;

    /**
     * Fields that should only be read from the database when they are
     * accessed.
     *
     * @var array
     */
    protected $_lazyFields = array();

    /**
     * Relationships for this entity.
     *
     * @var array
     */
    protected $_relationships = array();

    /**
     * Relationships that should only be read from the database when
     * they are accessed.
     *
     * @var array
     */
    protected $_lazyRelationships = array();

    /**
     * Default sorting rule to use for all queries made with this mapper. This
     * is a SQL ORDER BY fragment (without 'ORDER BY').
     *
     * @var string
     */
    protected $_defaultSort;

    /**
     * The caching factory, if used
     *
     * @var Horde_Rdo_Factory
     */
    protected $_factory = null;

    public function __construct(Horde_Db_Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Attach a Horde_Rdo_Factory to the mapper.
     * If called without arguments, detaches the mapper from factory
     *
     * @param Horde_Rdo_Factory $factory  A Factory instance or null
     * @return Horde_Rdo_Mapper  this mapper
     */
    public function setFactory(Horde_Rdo_Factory $factory = null)
    {
        $this->_factory = $factory;
        return $this;
    }

    /**
     * Provide read-only, on-demand access to several properties. This
     * method will only be called for properties that aren't already
     * present; once a property is fetched once it is cached and
     * returned directly on any subsequent access.
     *
     * These properties are available:
     *
     * adapter: The Horde_Db_Adapter this mapper is using to talk to
     * the database.
     *
     * factory: The Horde_Rdo_Factory instance, if present
     *
     * inflector: The Horde_Support_Inflector this Mapper uses to singularize
     * and pluralize PHP class, database table, and database field/key names.
     *
     * table: The database table or view that this Mapper manages.
     *
     * tableDefinition: The Horde_Db_Adapter_Base_TableDefinition object describing
     * the table or view this Mapper manages.
     *
     * fields: Array of all field names that are loaded up front
     * (eager loading) from the table.
     *
     * lazyFields: Array of fields that are only loaded when accessed.
     *
     * relationships: Array of relationships to other Mappers.
     *
     * lazyRelationships: Array of relationships to other Mappers which
     * are only loaded when accessed.
     *
     * @param string $key Property name to fetch
     *
     * @return mixed Value of $key
     */
    public function __get($key)
    {
        switch ($key) {
        case 'inflector':
            $this->inflector = new Horde_Support_Inflector();
            return $this->inflector;

        case 'primaryKey':
            $this->primaryKey = (string)$this->tableDefinition->getPrimaryKey();
            return $this->primaryKey;

        case 'table':
            $this->table = !empty($this->_table) ? $this->_table : $this->mapperToTable();
            return $this->table;

        case 'tableDefinition':
            $this->tableDefinition = $this->adapter->table($this->table);
            return $this->tableDefinition;

        case 'fields':
            $this->fields = array_diff($this->tableDefinition->getColumnNames(), $this->_lazyFields);
            return $this->fields;

        case 'lazyFields':
        case 'relationships':
        case 'lazyRelationships':
        case 'factory':
        case 'defaultSort':
            return $this->{'_' . $key};
        }

        return null;
    }

    /**
     * Create an instance of $this->_classname from a set of data.
     *
     * @param array $fields Field names/default values for the new object.
     *
     * @see $_classname
     *
     * @return Horde_Rdo_Base An instance of $this->_classname with $fields
     * as initial data.
     */
    public function map($fields = array())
    {
        // Guess a classname if one isn't explicitly set.
        if (!$this->_classname) {
            $this->_classname = $this->mapperToEntity();
            if (!$this->_classname) {
                throw new Horde_Rdo_Exception('Unable to find an entity class (extending Horde_Rdo_Base) for ' . get_class($this));
            }
        }

        $o = new $this->_classname();
        $o->setMapper($this);

        $this->mapFields($o, $fields);

        if (is_callable(array($o, 'afterMap'))) {
            $o->afterMap();
        }

        return $o;
    }

    /**
     * Update an instance of $this->_classname from a set of data.
     *
     * @param Horde_Rdo_Base $object The object to update
     * @param array $fields Field names/default values for the object
     */
    public function mapFields($object, $fields = array())
    {
        $relationships = array();
        foreach ($fields as $fieldName => &$fieldValue) {
            if (strpos($fieldName, '@') !== false) {
                list($rel, $field) = explode('@', $fieldName, 2);
                $relationships[$rel][$field] = $fieldValue;
                unset($fields[$fieldName]);
            }
            if (isset($this->fields[$fieldName])) {
                $fieldName = $this->fields[$fieldName];
            }
            $column = $this->tableDefinition->getColumn($fieldName);
            if ($column) {
                $fieldValue = $column->typeCast($fieldValue);
            }
        }

        $object->setFields($fields);

        if (count($relationships)) {
            foreach ($this->relationships as $relationship => $rel) {
                if (isset($rel['mapper'])) {
                    if ($this->_factory) {
                        $m = $this->_factory->create($rel['mapper']);
                    }
                    // @TODO - should be getting this instance from somewhere
                    // else external, and not passing the adapter along
                    // automatically.
                    else {
                        $m = new $rel['mapper']($this->adapter);
                    }
                } else {
                    $m = $this->tableToMapper($relationship);
                    if (is_null($m)) {
                        // @TODO Throw an exception?
                        continue;
                    }
                }

                if (isset($relationships[$m->table])) {
                    $object->$relationship = $m->map($relationships[$m->table]);
                }
            }
        }
    }

    /**
     * Transform a table name to a mapper class name.
     *
     * @param string $table The database table name to look up.
     *
     * @return Horde_Rdo_Mapper A new Mapper instance if it exists, else null.
     */
    public function tableToMapper($table)
    {
        if (class_exists(($class = Horde_String::ucwords($table) . 'Mapper'))) {
            return new $class;
        }
        return null;
    }

    /**
     * Transform this mapper's class name to a database table name.
     *
     * @return string The database table name.
     */
    public function mapperToTable()
    {
        return $this->inflector->pluralize(Horde_String::lower(str_replace('Mapper', '', get_class($this))));
    }

    /**
     * Transform this mapper's class name to an entity class name.
     *
     * @return string A Horde_Rdo_Base concrete class name if the class exists, else null.
     */
    public function mapperToEntity()
    {
        $class = str_replace('Mapper', '', get_class($this));
        if (class_exists($class)) {
            return $class;
        }
        return null;
    }

    /**
     * Count objects that match $query.
     *
     * @param mixed $query The query to count matches of.
     *
     * @return integer All objects matching $query.
     */
    public function count($query = null)
    {
        $query = Horde_Rdo_Query::create($query, $this);
        $query->setFields('COUNT(*)')
              ->clearSort();
        list($sql, $bindParams) = $query->getQuery();
        return $this->adapter->selectValue($sql, $bindParams);
    }

    /**
     * Check if at least one object matches $query.
     *
     * @param mixed $query Either a primary key, an array of keys
     *                     => values, or a Horde_Rdo_Query object.
     *
     * @return boolean True or false.
     */
    public function exists($query)
    {
        $query = Horde_Rdo_Query::create($query, $this);
        $query->setFields(1)
              ->clearSort();
        list($sql, $bindParams) = $query->getQuery();
        return (bool)$this->adapter->selectValue($sql, $bindParams);
    }

    /**
     * Create a new object in the backend with $fields as initial values.
     *
     * @param array $fields Array of field names => initial values.
     *
     * @return Horde_Rdo_Base The newly created object.
     */
    public function create($fields)
    {
        // If configured to record creation and update times, set them
        // here. We set updated_at to the initial creation time so it's
        // always set.
        if ($this->_setTimestamps) {
            $time = time();
            $fields['created_at'] = $time;
            $fields['updated_at'] = $time;
        }

        // Filter out any extra fields.
        $fields = array_intersect_key($fields, array_flip($this->tableDefinition->getColumnNames()));

        if (!$fields) {
            throw new Horde_Rdo_Exception('create() requires at least one field value.');
        }

        $sql = 'INSERT INTO ' . $this->adapter->quoteTableName($this->table);
        $keys = array();
        $placeholders = array();
        $bindParams = array();
        foreach ($fields as $field => $value) {
            $keys[] = $this->adapter->quoteColumnName($field);
            $placeholders[] = '?';
            $bindParams[] = $value;
        }
        $sql .= ' (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $placeholders) . ')';

        $id = $this->adapter->insert($sql, $bindParams);

        return $this->map(array_merge($fields, array($this->primaryKey => $id)));
    }

    /**
     * Updates a record in the backend. $object can be either a
     * primary key or an Rdo object. If $object is an Rdo instance
     * then $fields will be ignored as values will be pulled from the
     * object.
     *
     * @param string|Rdo $object The Rdo instance or unique id to update.
     * @param array $fields If passing a unique id, the array of field properties
     *                      to set for $object.
     *
     * @return integer Number of objects updated.
     */
    public function update($object, $fields = null)
    {
        if ($object instanceof Horde_Rdo_Base) {
            $key = $this->primaryKey;
            $id = $object->$key;
            $fields = iterator_to_array($object);

            if (!$id) {
                // Object doesn't exist yet; create it instead.
                $o = $this->create($fields);
                $this->mapFields($object, iterator_to_array($o));
                return 1;
            }
        } else {
            $id = $object;
        }

        // If configured to record update time, set it here.
        if ($this->_setTimestamps) {
            $fields['updated_at'] = time();
        }

        // Filter out any extra fields.
        $fields = array_intersect_key($fields, array_flip($this->tableDefinition->getColumnNames()));

        if (!$fields) {
            // Nothing to change.
            return 0;
        }

        $sql = 'UPDATE ' . $this->adapter->quoteTableName($this->table) . ' SET';
        $bindParams = array();
        foreach ($fields as $field => $value) {
            $sql .= ' ' . $this->adapter->quoteColumnName($field) . ' = ?,';
            $bindParams[] = $value;
        }
        $sql = substr($sql, 0, -1) . ' WHERE ' . $this->primaryKey . ' = ?';
        $bindParams[] = $id;

        return $this->adapter->update($sql, $bindParams);
    }

    /**
     * Deletes a record from the backend. $object can be either a
     * primary key, an Rdo_Query object, or an Rdo object.
     *
     * @param string|Horde_Rdo_Base|Horde_Rdo_Query $object The Rdo object,
     * Horde_Rdo_Query, or unique id to delete.
     *
     * @return integer Number of objects deleted.
     */
    public function delete($object)
    {
        if ($object instanceof Horde_Rdo_Base) {
            $key = $this->primaryKey;
            $id = $object->$key;
            $query = array($key => $id);
        } elseif ($object instanceof Horde_Rdo_Query) {
            $query = $object;
        } else {
            $key = $this->primaryKey;
            $query = array($key => $object);
        }

        $query = Horde_Rdo_Query::create($query, $this);

        $clauses = array();
        $bindParams = array();
        foreach ($query->tests as $test) {
            $clauses[] = $this->adapter->quoteColumnName($test['field']) . ' ' . $test['test'] . ' ?';
            $bindParams[] = $test['value'];
        }
        if (!$clauses) {
            throw new Horde_Rdo_Exception('Refusing to delete the entire table.');
        }

        $sql = 'DELETE FROM ' . $this->adapter->quoteTableName($this->table) .
               ' WHERE ' . implode(' ' . $query->conjunction . ' ', $clauses);

        return $this->adapter->delete($sql, $bindParams);
    }

    /**
     * find() can be called in several ways.
     *
     * Primary key mode: pass find() a numerically indexed array of primary
     * keys, and it will return a list of the objects that correspond to those
     * keys.
     *
     * If you pass find() no arguments, all objects of this type will be
     * returned.
     *
     * If you pass find() an associative array, it will be turned into a
     * Horde_Rdo_Query object.
     *
     * If you pass find() a Horde_Rdo_Query, it will return a list of all
     * objects matching that query.
     */
    public function find($arg = null)
    {
        if (is_null($arg)) {
            $query = null;
        } elseif (is_array($arg)) {
            if (!count($arg)) {
                throw new Horde_Rdo_Exception('No criteria found');
            }

            if (is_numeric(key($arg))) {
                // Numerically indexed arrays are assumed to be an array of
                // primary keys.
                $query = new Horde_Rdo_Query();
                $query->combineWith('OR');
                foreach ($argv[0] as $id) {
                    $query->addTest($this->primaryKey, '=', $id);
                }
            } else {
                $query = $arg;
            }
        } else {
            $query = $arg;
        }

        // Build a full Query object.
        $query = Horde_Rdo_Query::create($query, $this);
        return new Horde_Rdo_List($query);
    }

    /**
     * Adds a relation.
     *
     * - For one-to-one relations, simply updates the relation field.
     * - For one-to-many relations, updates the related object's relation field.
     * - For many-to-many relations, adds an entry in the "through" table.
     * - Performs a no-op if the peer is already related.
     *
     * @param string $relationship    The relationship key in the mapper.
     * @param Horde_Rdo_Base $ours    The object from this mapper to add the
     *                                relation.
     * @param Horde_Rdo_Base $theirs  The other object from any mapper to add
     *                                the relation.
     *
     * @throws Horde_Rdo_Exception
     */
    public function addRelation($relationship, Horde_Rdo_Base $ours,
                                Horde_Rdo_Base $theirs)
    {
        if ($ours->hasRelation($relationship, $theirs)) {
            return;
        }

        $ourKey = $this->primaryKey;
        $theirKey = $theirs->mapper->primaryKey;

        if (isset($this->relationships[$relationship])) {
            $rel = $this->relationships[$relationship];
        } elseif (isset($this->lazyRelationships[$relationship])) {
            $rel = $this->lazyRelationships[$relationship];
        } else {
            throw new Horde_Rdo_Exception('The requested relation is not defined in the mapper');
        }

        switch ($rel['type']) {
        case Horde_Rdo::ONE_TO_ONE:
        case Horde_Rdo::MANY_TO_ONE:
            $ours->{$rel['foreignKey']} = $theirs->$theirKey;
            $ours->save();
            break;

        case Horde_Rdo::ONE_TO_MANY:
            $theirs->{$rel['foreignKey']} = $ours->$ourKey;
            $theirs->save();
            break;

        case Horde_Rdo::MANY_TO_MANY:
            $sql = sprintf('INSERT INTO %s (%s, %s) VALUES (?, ?)',
                           $this->adapter->quoteTableName($rel['through']),
                           $this->adapter->quoteColumnName($ourKey),
                           $this->adapter->quoteColumnName($theirKey));
            try {
                $this->adapter->insert($sql, array($ours->$ourKey, $theirs->$theirKey));
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Rdo_Exception($e);
            }
            break;
        }
    }

    /**
     * Removes a relation to one of the relationships defined in the mapper.
     *
     * - For one-to-one and one-to-many relations, simply sets the relation
     *   field to 0.
     * - For many-to-many, either deletes all relations to this object or just
     *   the relation to a given peer object.
     * - Performs a no-op if the peer is already unrelated.
     *
     * This is a proxy to the mapper's removeRelation method.
     *
     * @param string $relationship    The relationship key in the mapper.
     * @param Horde_Rdo_Base $ours    The object from this mapper.
     * @param Horde_Rdo_Base $theirs  The object to remove from the relation.
     * @return integer  the number of affected relations
     *
     * @throws Horde_Rdo_Exception
     */
    public function removeRelation($relationship, Horde_Rdo_Base $ours,
                                   Horde_Rdo_Base $theirs = null)
    {
        if (!$ours->hasRelation($relationship, $theirs)) {
            return;
        }

        $ourKey = $this->primaryKey;

        if (isset($this->relationships[$relationship])) {
            $rel = $this->relationships[$relationship];
        } elseif (isset($this->lazyRelationships[$relationship])) {
            $rel = $this->lazyRelationships[$relationship];
        } else {
            throw new Horde_Rdo_Exception('The requested relation is not defined in the mapper');
        }

        switch ($rel['type']) {
        case Horde_Rdo::ONE_TO_ONE:
        case Horde_Rdo::MANY_TO_ONE:
            $ours->{$rel['foreignKey']} = null;
            $ours->save();
            return 1;
            break;

        case Horde_Rdo::ONE_TO_MANY:
            $theirs->{$rel['foreignKey']} = null;
            $theirs->save();
            return 1;
            break;

        case Horde_Rdo::MANY_TO_MANY:
            $sql = sprintf('DELETE FROM %s WHERE %s = ? ',
                           $this->adapter->quoteTableName($rel['through']),
                           $this->adapter->quoteColumnName($ourKey));
            $values = array($ours->$ourKey);
            if (!empty($theirs)) {
                $theirKey = $theirs->mapper->primaryKey;
                $sql .= sprintf(' AND %s = ?',
                                $this->adapter->quoteColumnName($theirKey));
                $values[] = $theirs->$theirKey;
            }
            try {
                return $this->adapter->delete($sql, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Rdo_Exception($e);
            }
            break;
        }
    }

    /**
     * findOne can be called in several ways.
     *
     * Primary key mode: pass find() a single primary key, and it will return a
     * single object matching that primary key.
     *
     * If you pass findOne() no arguments, the first object of this type will be
     * returned.
     *
     * If you pass findOne() an associative array, it will be turned into a
     * Horde_Rdo_Query object.
     *
     * If you pass findOne() a Horde_Rdo_Query, it will return the first object
     * matching that query.
     */
    public function findOne($arg = null)
    {
        if (is_null($arg)) {
            $query = null;
        } elseif (is_scalar($arg)) {
            $query = array($this->primaryKey => $arg);
        } else {
            $query = $arg;
        }

        // Build a full Query object, and limit it to one result.
        $query = Horde_Rdo_Query::create($query, $this);
        $query->limit(1);

        $list = new Horde_Rdo_List($query);
        return $list->current();
    }

    /**
     * Set a default sort rule for all queries done with this Mapper.
     *
     * @param string $sort SQL sort fragment, such as 'updated DESC'
     */
    public function sortBy($sort)
    {
        $this->_defaultSort = $sort;
        return $this;
    }
}
