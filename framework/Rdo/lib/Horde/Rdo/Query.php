<?php
/**
 * Represent a single query or a tree of many query elements uniformly to clients.
 *
 * @category Horde
 * @package  Horde_Rdo
 */

/**
 * @category Horde
 * @package  Horde_Rdo
 */
class Horde_Rdo_Query
{
    /**
     * @var Horde_Rdo_Mapper
     */
    public $mapper;

    /**
     * @var string
     */
    public $conjunction = 'AND';

    /**
     * @var array
     */
    public $fields = array('*');

    /**
     * @var array
     */
    public $tests = array();

    /**
     * @var array
     */
    public $relationships = array();

    /**
     * @var integer
     */
    public $limit;

    /**
     * @var integer
     */
    public $limitOffset = null;

    /**
     * @var array
     */
    protected $_sortby = array();

    /**
     * @var integer
     */
    protected $_aliasCount = 0;

    /**
     * @var array
     */
    protected $_aliases = array();

    /**
     * Turn any of the acceptable query shorthands into a full
     * Horde_Rdo_Query object. If you pass an existing Horde_Rdo_Query
     * object in, it will be cloned before it's returned so that it
     * can be safely modified.
     *
     * @param mixed $query The query to convert to an object.
     * @param Horde_Rdo_Mapper $mapper The Mapper object governing this query.
     *
     * @return Horde_Rdo_Query The full Horde_Rdo_Query object.
     */
    public static function create($query, $mapper = null)
    {
        if ($query instanceof Horde_Rdo_Query ||
            $query instanceof Horde_Rdo_Query_Literal) {
            $query = clone $query;
            if (!is_null($mapper)) {
                $query->setMapper($mapper);
            }
            return $query;
        }

        $q = new Horde_Rdo_Query($mapper);

        if (is_scalar($query)) {
            $q->addTest($mapper->tableDefinition->getPrimaryKey(), '=', $query);
        } elseif ($query) {
            $q->combineWith('AND');
            foreach ($query as $key => $value) {
                $q->addTest($key, '=', $value);
            }
        }

        return $q;
    }

    /**
     * @param  Horde_Rdo_Mapper  $mapper  Rdo mapper base class
     */
    public function __construct($mapper = null)
    {
        $this->setMapper($mapper);
    }

    /**
     * @param Horde_Rdo_Mapper $mapper Rdo mapper base class
     *
     * @return Horde_Rdo_Query Return the query object for fluent chaining.
     */
    public function setMapper($mapper)
    {
        if ($mapper === $this->mapper) {
            return $this;
        }

        $this->mapper = $mapper;

        // Fetch all non-lazy-loaded fields for the mapper.
        $this->setFields($mapper->fields, $mapper->table . '.');

        if (!is_null($mapper)) {
            // Add all non-lazy relationships.
            foreach ($mapper->relationships as $relationship => $rel) {
                if (isset($rel['mapper'])) {
                    $m = new $rel['mapper']();
                } else {
                    $m = $this->mapper->tableToMapper($relationship);
                    if (is_null($m)) {
                        throw new Horde_Rdo_Exception('Unable to find a Mapper class for eager-loading relationship ' . $relationship);
                    }
                }

                // Add the fields for this relationship to the query.
                $m->tableAlias = $this->_alias($m->table);
                $this->addFields($m->fields, $m->tableAlias . '.@');

                switch ($rel['type']) {
                case Horde_Rdo::ONE_TO_ONE:
                case Horde_Rdo::MANY_TO_ONE:
                    if (isset($rel['query'])) {
                        $query = $this->_fillJoinPlaceholders($m, $mapper, $rel['query']);
                    } else {
                        $query = array($mapper->table . '.' . $rel['foreignKey'] => new Horde_Rdo_Query_Literal($m->table . '.' . $m->tableDefinition->getPrimaryKey()));
                    }
                    $this->addRelationship($relationship, array('mapper' => $m,
                                                                'type' => $rel['type'],
                                                                'query' => $query));
                    break;

                case Horde_Rdo::ONE_TO_MANY:
                case Horde_Rdo::MANY_TO_MANY:
                    //@TODO
                }
            }
        }

        return $this;
    }

    /**
     * @param array $fields The fields to load with this query.
     *
     * @return Horde_Rdo_Query Returns self for fluent method chaining.
     */
    public function setFields($fields, $fieldPrefix = null)
    {
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        if (!is_null($fieldPrefix)) {
            array_walk($fields, array($this, '_prefix'), $fieldPrefix);
        }
        $this->fields = $fields;
        return $this;
    }

    /**
     * @param array $fields Additional Fields to load with this query.
     *
     * @return Horde_Rdo_Query Returns self for fluent method chaining.
     */
    public function addFields($fields, $fieldPrefix = null)
    {
        if (!is_null($fieldPrefix)) {
            array_walk($fields, array($this, '_prefix'), $fieldPrefix);
        }
        $this->fields = array_merge($this->fields, $fields);
    }

    /**
     * @param string $conjunction SQL conjunction such as "AND", "OR".
     */
    public function combineWith($conjunction)
    {
        $this->conjunction = $conjunction;
        return $this;
    }

    /**
     */
    public function addTest($field, $test, $value)
    {
        $this->tests[] = array('field' => $field,
                               'test'  => $test,
                               'value' => $value);
        return $this;
    }

    /**
     */
    public function addRelationship($relationship, $args)
    {
        if (!isset($args['mapper'])) {
            throw new InvalidArgumentException('Relationships must contain a Horde_Rdo_Mapper object.');
        }
        if (!isset($args['table'])) {
            $args['table'] = $args['mapper']->table;
        }
        if (!isset($args['tableAlias'])) {
            if (isset($args['mapper']->tableAlias)) {
                $args['tableAlias'] = $args['mapper']->tableAlias;
            } else {
                $args['tableAlias'] = $this->_alias($args['table']);
            }
        }
        if (!isset($args['type'])) {
            $args['type'] = Horde_Rdo::MANY_TO_MANY;
        }
        if (!isset($args['join_type'])) {
            switch ($args['type']) {
            case Horde_Rdo::ONE_TO_ONE:
            case Horde_Rdo::MANY_TO_ONE:
                $args['join_type'] = 'INNER JOIN';
                break;

            default:
                $args['join_type'] = 'LEFT JOIN';
            }
        }

        $this->relationships[$relationship] = $args;
        return $this;
    }

    /**
     * Add a sorting rule.
     *
     * @param string $sort SQL sort fragment, such as 'updated DESC'
     */
    public function sortBy($sort)
    {
        $this->_sortby[] = $sort;
        return $this;
    }

    /**
     */
    public function clearSort()
    {
        $this->_sortby = array();
        return $this;
    }

    /**
     * Restrict the query to a subset of the results.
     *
     * @param integer $limit Number of items to fetch.
     * @param integer $offset Offset to start fetching at.
     */
    public function limit($limit, $offset = null)
    {
        $this->limit = $limit;
        $this->limitOffset = $offset;
        return $this;
    }

    /**
     * Accessor for any fields that we want some logic around.
     *
     * @param string $key
     */
    public function __get($key)
    {
        switch ($key) {
        case 'sortby':
            if (!$this->_sortby && $this->mapper->defaultSort) {
                // Add in any default sort values, if none are already
                // set.
                $this->sortBy($this->mapper->defaultSort);
            }
            return $this->_sortby;
        }

        throw new InvalidArgumentException('Undefined property ' . $key);
    }

    /**
     * Query generator.
     *
     * @return array A two-element array of the SQL query and an array
     * of bind parameters.
     */
    public function getQuery()
    {
        $bindParams = array();
        $sql = '';

        $this->_select($sql, $bindParams);
        $this->_from($sql, $bindParams);
        $this->_join($sql, $bindParams);
        $this->_where($sql, $bindParams);
        $this->_orderBy($sql, $bindParams);
        $this->_limit($sql, $bindParams);

        return array($sql, $bindParams);
    }

    /**
     */
    protected function _select(&$sql, &$bindParams)
    {
        $fields = array();
        foreach ($this->fields as $field) {
            $parts = explode('.@', $field, 2);
            if (count($parts) == 1) {
                $fields[] = $field;
            } else {
                list($tableName, $columnName) = $parts;
                if (isset($this->_aliases[$tableName])) {
                    $tableName = $this->_aliases[$tableName];
                }
                $fields[] = str_replace('.@', '.', $field) . ' AS ' . $this->mapper->adapter->quoteColumnName($tableName . '@' . $columnName);
            }
        }

        $sql = 'SELECT ' . implode(', ', $fields);
    }

    /**
     */
    protected function _from(&$sql, &$bindParams)
    {
        $sql .= ' FROM ' . $this->mapper->table;
    }

    /**
     */
    protected function _join(&$sql, &$bindParams)
    {
        foreach ($this->relationships as $relationship) {
            $relsql = array();
            $table = $relationship['table'];
            $tableAlias = $relationship['tableAlias'];
            foreach ($relationship['query'] as $key => $value) {
                if ($value instanceof Horde_Rdo_Query_Literal) {
                    $relsql[] = $key . ' = ' . str_replace("{$table}.", "{$tableAlias}.", (string)$value);
                } else {
                    $relsql[] = $key . ' = ?';
                    $bindParams[] = $value;
                }
            }

            $sql .= ' ' . $relationship['join_type'] . ' ' . $relationship['table'] . ' AS ' . $tableAlias . ' ON ' . implode(' AND ', $relsql);
        }
    }

    /**
     */
    protected function _where(&$sql, &$bindParams)
    {
        $clauses = array();
        foreach ($this->tests as $test) {
            if (strpos($test['field'], '@') !== false) {
                list($rel, $field) = explode('@', $test['field']);
                if (!isset($this->relationships[$rel])) {
                    continue;
                }
                $clause = $this->relationships[$rel]['tableAlias'] . '.' . $field . ' ' . $test['test'];
            } else {
                $clause = $this->mapper->table . '.' . $this->mapper->adapter->quoteColumnName($test['field']) . ' ' . $test['test'];
            }

            if ($test['value'] instanceof Horde_Rdo_Query_Literal) {
                $clauses[] = $clause . ' ' . (string)$test['value'];
            } else {
                if ($test['test'] == 'IN' && is_array($test['value'])) {
                    $clauses[] = $clause . '(?' . str_repeat(',?', count($test['value']) - 1) . ')';
                    $bindParams = array_merge($bindParams, array_values($test['value']));
                } else {
                    $clauses[] = $clause . ' ?';
                    $bindParams[] = $test['value'];
                }
            }
        }

        if ($clauses) {
            $sql .= ' WHERE ' . implode(' ' . $this->conjunction . ' ', $clauses);
        }
    }

    /**
     */
    protected function _orderBy(&$sql, &$bindParams)
    {
        if ($this->sortby) {
            $sql .= ' ORDER BY';
            foreach ($this->sortby as $sort) {
                if (strpos($sort, '@') !== false) {
                    list($rel, $field) = explode('@', $sort);
                    if (!isset($this->relationships[$rel])) {
                        continue;
                    }
                    $sql .= ' ' . $this->relationships[$rel]['tableAlias'] . '.' . $field . ',';
                } else {
                    $sql .= " $sort,";
                }
            }

            $sql = substr($sql, 0, -1);
        }
    }

    /**
     */
    protected function _limit(&$sql, &$bindParams)
    {
        if ($this->limit) {
            $opts = array('limit' => $this->limit, 'offset' => $this->limitOffset);
            $sql = $this->mapper->adapter->addLimitOffset($sql, $opts);
        }
    }

    /**
     * Callback for array_walk to prefix all elements of an array with
     * a given prefix.
     */
    protected function _prefix(&$fieldName, $key, $prefix)
    {
        $fieldName = $prefix . $fieldName;
    }

    /**
     * Get a unique table alias
     */
    protected function _alias($tableName)
    {
        $alias = 't' . ++$this->_aliasCount;
        $this->_aliases[$alias] = $tableName;
        return $alias;
    }

    /**
     * Take a query array and replace @field@ placeholders with values
     * that will match in the load query.
     *
     * @param Horde_Rdo_Mapper $m1 Left-hand mapper
     * @param Horde_Rdo_Mapper $m2 Right-hand mapper
     * @param array $query The query to process placeholders on.
     *
     * @return array The query with placeholders filled in.
     */
    protected function _fillJoinPlaceholders($m1, $m2, $query)
    {
        $q = array();
        foreach (array_keys($query) as $field) {
            $value = $query[$field];
            if (preg_match('/^@(.*)@$/', $value, $matches)) {
                $q[$m1->tableAlias . '.' . $field] = new Horde_Rdo_Query_Literal($m2->table . '.' . $matches[1]);
            } else {
                $q[$m1->tableAlias . '.' . $field] = $value;
            }
        }

        return $q;
    }

}
