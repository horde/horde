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
class Horde_Db_Adapter_Postgresql_Schema extends Horde_Db_Adapter_Base_Schema
{
    /**
     * @var string
     */
    protected $_schemaSearchPath = '';


    /*##########################################################################
    # Object factories
    ##########################################################################*/

    /**
     * Factory for Column objects
     */
    public function makeColumn($name, $default, $sqlType = null, $null = true)
    {
        return new Horde_Db_Adapter_Postgresql_Column($name, $default, $sqlType, $null);
    }


    /*##########################################################################
    # Quoting
    ##########################################################################*/

    /**
     * Quotes column names for use in SQL queries.
     *
     * @return  string
     */
    public function quoteColumnName($name)
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    /**
     * Quotes sequence names for use in SQL queries.
     *
     * @return  string
     */
    public function quoteSequenceName($name)
    {
        return '\'' . str_replace('"', '""', $name) . '\'';
    }

    /**
     * Quotes PostgreSQL-specific data types for SQL input.
     */
    public function quote($value, $column = null)
    {
        if (!$column) {
            return parent::quote($value, $column);
        }

        if (is_string($value) &&
            $column->getType() == 'binary' &&
            method_exists($column, 'stringToBinary')) {
            /*@TODO test blobs/bytea fields with postgres/pdo and figure out how
              this should work */
            return $this->quotedStringPrefix() . "'" . $column->stringToBinary($value) . "'";
        } elseif (is_string($value) && $column->getSqlType() == 'xml') {
            return "xml '" . $this->quoteString($value) . "'";
        } elseif (is_numeric($value) && $column->getSqlType() == 'money') {
            // Not truly string input, so doesn't require (or allow) escape string syntax.
            return "'" . $value . "'";
        } elseif (is_string($value) &&
                  substr($column->getSqlType(), 0, 3) == 'bit') {
            if (preg_match('/^[01]*$/', $value)) {
                // Bit-string notation
                return "B'" . $value . "'";
            } elseif (preg_match('/^[0-9A-F]*$/i')) {
                // Hexadecimal notation
                return "X'" . $value . "'";
            }
        }

        return parent::quote($value, $column);
    }


    /*##########################################################################
    # Schema Statements
    ##########################################################################*/

    /**
     * The db column types for this adapter
     *
     * @return  array
     */
    public function nativeDatabaseTypes()
    {
        return array(
            'primaryKey' => 'serial primary key',
            'string'     => array('name' => 'character varying', 'limit' => 255),
            'text'       => array('name' => 'text',              'limit' => null),
            'integer'    => array('name' => 'integer',           'limit' => null),
            'float'      => array('name' => 'float',             'limit' => null),
            'decimal'    => array('name' => 'decimal',           'limit' => null),
            'datetime'   => array('name' => 'timestamp',         'limit' => null),
            'timestamp'  => array('name' => 'timestamp',         'limit' => null),
            'time'       => array('name' => 'time',              'limit' => null),
            'date'       => array('name' => 'date',              'limit' => null),
            'binary'     => array('name' => 'bytea',             'limit' => null),
            'boolean'    => array('name' => 'boolean',           'limit' => null),
        );
    }

    /**
     * Returns the configured supported identifier length supported by PostgreSQL,
     * or report the default of 63 on PostgreSQL 7.x.
     */
    public function tableAliasLength()
    {
        if ($this->postgresqlVersion() >= 80000) {
            return (int)$this->selectValue('SHOW max_identifier_length');
        } else return 63;
    }

    /**
     * Creates a new PostgreSQL database.
     *
     * Options include: owner, template, charset, tablespace, and
     * connection_limit.
     */
    public function createDatabase($name, $options = array())
    {
        $options = array_merge(array('charset' => 'utf8'), $options);

        $optionString = '';
        foreach ($options as $key => $value) {
            switch ($key) {
            case 'owner':
                $optionString .= " OWNER = '$value'";
                break;
            case 'template':
                $optionString .= " TEMPLATE = $value";
                break;
            case 'charset':
                $optionString .= " ENCODING = '$value'";
                break;
            case 'tablespace':
                $optionString .= " TABLESPACE = $value";
            case 'connection_limit':
                $optionString .= " CONNECTION LIMIT = $value";
            }
        }

        return $this->execute('CREATE DATABASE ' . $this->quoteTableName($name) . $optionString);
    }

    /**
     * Drops a PostgreSQL database
     *
     * Example:
     *   dropDatabase('matt_development')
     */
    public function dropDatabase($name)
    {
        if ($this->postgresqlVersion() >= 80200) {
            return $this->execute('DROP DATABASE IF EXISTS ' . $this->quoteTableName($name));
        } else {
            try {
                return $this->execute('DROP DATABASE ' . $this->quoteTableName($name));
            } catch (Horde_Db_Exception $e) {
                if ($this->_logger) { $this->_logger->warn("$name database doesn't exist"); }
            }
        }
    }

    /**
     * Returns the current database name.
     */
    public function currentDatabase()
    {
        return $this->selectValue('SELECT current_database()');
    }

    /**
     * Returns the list of all tables in the schema search path or a specified schema.
     */
    public function tables($name = null)
    {
        $schemas = array();
        foreach (explode(',', $this->getSchemaSearchPath()) as $p) {
            $schemas[] = $this->quote($p);
        }

        return $this->selectValues('SELECT tablename FROM pg_tables WHERE schemaname IN (' . implode(',', $schemas) . ')', $name);
    }

    /**
     * Return a table's primary key
     */
    public function primaryKey($tableName, $name = null)
    {
        $sql = sprintf('SELECT column_name FROM information_schema.constraint_column_usage WHERE table_name = %s AND constraint_name = %s',
                       $this->quoteString($tableName),
                       $this->quoteString($tableName . '_pkey'));
        $pk = $this->selectValues($sql, $name);

        return $this->makeIndex($tableName, 'PRIMARY', true, true, $pk);
    }

    /**
     * Returns the list of all indexes for a table.
     */
    public function indexes($tableName, $name = null)
    {
        $indexes = @unserialize($this->_cache->get("tables/indexes/$tableName"));

        if (!$indexes) {
            $schemas = array();
            foreach (explode(',', $this->getSchemaSearchPath()) as $p) {
                $schemas[] = $this->quote($p);
            }

            $sql = "
              SELECT distinct i.relname, d.indisunique, a.attname
                 FROM pg_class t, pg_class i, pg_index d, pg_attribute a
              WHERE i.relkind = 'i'
                 AND d.indexrelid = i.oid
                 AND d.indisprimary = 'f'
                 AND t.oid = d.indrelid
                 AND t.relname = " . $this->quote($tableName) . "
                 AND i.relnamespace IN (SELECT oid FROM pg_namespace WHERE nspname IN (" . implode(',', $schemas) . ") )
                 AND a.attrelid = t.oid
                 AND (d.indkey[0] = a.attnum OR d.indkey[1] = a.attnum
                   OR d.indkey[2] = a.attnum OR d.indkey[3] = a.attnum
                   OR d.indkey[4] = a.attnum OR d.indkey[5] = a.attnum
                   OR d.indkey[6] = a.attnum OR d.indkey[7] = a.attnum
                   OR d.indkey[8] = a.attnum OR d.indkey[9] = a.attnum)
              ORDER BY i.relname";

            $result = $this->select($sql, $name);

            $currentIndex = null;
            $indexes = array();

            foreach ($result as $row) {
                if ($currentIndex != $row['relname']) {
                    $currentIndex = $row['relname'];
                    $indexes[] = $this->makeIndex(
                        $tableName, $row['relname'], false, $row['indisunique'] == 't', array());
                }
                $indexes[count($indexes) - 1]->columns[] = $row['attname'];
            }

            $this->_cache->set("tables/indexes/$tableName", serialize($indexes));
        }

        return $indexes;
    }

    /**
     * Returns the list of all column definitions for a table.
     */
    public function columns($tableName, $name = null)
    {
        $rows = @unserialize($this->_cache->get("tables/columns/$tableName"));

        if (!$rows) {
            $rows = $this->columnDefinitions($tableName, $name);

            $this->_cache->set("tables/columns/$tableName", serialize($rows));
        }

        // create columns from rows
        $columns = array();
        foreach ($rows as $row) {
            $columns[$row['attname']] = $this->makeColumn(
                $row['attname'], $row['adsrc'], $row['format_type'], !(boolean)$row['attnotnull']);
        }
        return $columns;
    }

    /**
     * Returns the current database encoding format.
     */
    public function encoding()
    {
        return $this->selectValue(
            'SELECT pg_encoding_to_char(pg_database.encoding) FROM pg_database
             WHERE pg_database.datname LIKE ' . $this->quote($this->currentDatabase()));
    }

    /**
     * Sets the schema search path to a string of comma-separated schema names.
     * Names beginning with $ have to be quoted (e.g. $user => '$user').
     * See: http://www.postgresql.org/docs/current/static/ddl-schemas.html
     *
     * This should be not be called manually but set in database.yml.
     */
    public function setSchemaSearchPath($schemaCsv)
    {
        if ($schemaCsv) {
            $this->execute('SET search_path TO ' . $schemaCsv);
            $this->_schemaSearchPath = $schemaCsv;
        }
    }

    /**
     * Returns the active schema search path.
     */
    public function getSchemaSearchPath()
    {
        if (!$this->_schemaSearchPath) {
            $this->_schemaSearchPath = $this->selectValue('SHOW search_path');
        }
        return $this->_schemaSearchPath;
    }

    /**
     * Returns the current client message level.
     */
    public function getClientMinMessages()
    {
        return $this->selectValue('SHOW client_min_messages');
    }

    /**
     * Set the client message level.
     */
    public function setClientMinMessages($level)
    {
        return $this->execute('SET client_min_messages TO ' . $this->quote($level));
    }

    /**
     * Returns the sequence name for a table's primary key or some other specified key.
     */
    public function defaultSequenceName($tableName, $pk = null)
    {
        list($defaultPk, $defaultSeq) = $this->pkAndSequenceFor($tableName);
        if (!$defaultSeq) {
            $defaultSeq = $tableName . '_' . ($pk ? $pk : ($defaultPk ? $defaultPk : 'id')) . '_seq';
        }
        return $defaultSeq;
    }

    /**
     * Resets the sequence of a table's primary key to the maximum value.
     */
    public function resetPkSequence($table, $pk = null, $sequence = null)
    {
        if (!($pk && $sequence)) {
            list($defaultPk, $efaultSequence) = $this->pkAndSequenceFor($table);
            if (!$pk) {
                $pk = $defaultPk;
            }
            if (!$sequence) {
                $sequence = $defaultSequence;
            }
        }

        if ($pk) {
            if ($sequence) {
                $quotedSequence = $this->quoteSequenceName($sequence);
                $quotedTable = $this->quoteTableName($table);
                $quotedPk = $this->quoteColumnName($pk);

                $sql = sprintf('SELECT setval(%s, (SELECT COALESCE(MAX(%s) + (SELECT increment_by FROM %s), (SELECT min_value FROM %s)) FROM %s), false)',
                               $quotedSequence,
                               $quotedPk,
                               $quotedSequence,
                               $quotedSequence,
                               $quotedTable);
                $this->selectValue($sql, 'Reset sequence');
            } else {
                if ($this->_logger) {
                    $this->_logger->warn(sprintf('%s has primary key %s with no default sequence', $table, $pk));
                }
            }
        }
    }

    /**
     * Returns a table's primary key and belonging sequence.
     */
    public function pkAndSequenceFor($table)
    {
        // First try looking for a sequence with a dependency on the
        // given table's primary key.
        $sql = "
          SELECT attr.attname, seq.relname
          FROM pg_class      seq,
               pg_attribute  attr,
               pg_depend     dep,
               pg_namespace  name,
               pg_constraint cons
          WHERE seq.oid       = dep.objid
            AND seq.relkind   = 'S'
            AND attr.attrelid = dep.refobjid
            AND attr.attnum   = dep.refobjsubid
            AND attr.attrelid = cons.conrelid
            AND attr.attnum   = cons.conkey[1]
            AND cons.contype  = 'p'
            AND dep.refobjid  = '$table'::regclass";
        $result = $this->selectOne($sql, 'PK and serial sequence');

        if (!$result) {
            // If that fails, try parsing the primary key's default value.
            // Support the 7.x and 8.0 nextval('foo'::text) as well as
            // the 8.1+ nextval('foo'::regclass).
            $sql = "
            SELECT attr.attname,
              CASE
                WHEN split_part(def.adsrc, '''', 2) ~ '.' THEN
                  substr(split_part(def.adsrc, '''', 2),
                         strpos(split_part(def.adsrc, '''', 2), '.')+1)
                ELSE split_part(def.adsrc, '''', 2)
              END
            FROM pg_class       t
            JOIN pg_attribute   attr ON (t.oid = attrelid)
            JOIN pg_attrdef     def  ON (adrelid = attrelid AND adnum = attnum)
            JOIN pg_constraint  cons ON (conrelid = adrelid AND adnum = conkey[1])
            WHERE t.oid = '$table'::regclass
              AND cons.contype = 'p'
              AND def.adsrc ~* 'nextval'";

            $result = $this->selectOne($sql, 'PK and custom sequence');
        }

        // [primary_key, sequence]
        return array($result['attname'], $result['relname']);
    }

    /**
     * Renames a table.
     */
    public function renameTable($name, $newName)
    {
        $this->_clearTableCache($name);

        return $this->execute(sprintf('ALTER TABLE %s RENAME TO %s', $this->quoteTableName($name), $this->quoteTableName($newName)));
    }

    /**
     * Adds a new column to the named table.
     * See TableDefinition#column for details of the options you can use.
     */
    public function addColumn($tableName, $columnName, $type,
                              $options = array())
    {
        $this->_clearTableCache($tableName);

        $autoincrement = isset($options['autoincrement']) ? $options['autoincrement'] : null;
        $limit         = isset($options['limit'])         ? $options['limit']     : null;
        $precision     = isset($options['precision'])     ? $options['precision'] : null;
        $scale         = isset($options['scale'])         ? $options['scale']     : null;

        $sqltype = $this->typeToSql($type, $limit, $precision, $scale);

        /* Convert to SERIAL type if needed. */
        if ($autoincrement) {
            switch ($sqltype) {
            case 'bigint':
                $sqltype = 'BIGSERIAL';
                break;

            case 'integer':
            default:
                $sqltype = 'SERIAL';
                break;
            }
        }

        // Add the column.
        $sql = sprintf('ALTER TABLE %s ADD COLUMN %s %s',
                       $this->quoteTableName($tableName),
                       $this->quoteColumnName($columnName),
                       $sqltype);
        $this->execute($sql);

        $default = isset($options['default']) ? $options['default'] : null;
        $notnull = isset($options['null']) && $options['null'] === false;

        if (array_key_exists('default', $options)) {
            $this->changeColumnDefault($tableName, $columnName, $default);
        }

        if ($notnull) {
            $this->changeColumnNull($tableName, $columnName, false, $default);
        }
    }

    /**
     * Changes the column of a table.
     */
    public function changeColumn($tableName, $columnName, $type, $options = array())
    {
        $this->_clearTableCache($tableName);

        $autoincrement = isset($options['autoincrement']) ? $options['autoincrement'] : null;
        $limit         = isset($options['limit'])         ? $options['limit']     : null;
        $precision     = isset($options['precision'])     ? $options['precision'] : null;
        $scale         = isset($options['scale'])         ? $options['scale']     : null;

        $quotedTableName = $this->quoteTableName($tableName);

        try {
            $sql = sprintf('ALTER TABLE %s ALTER COLUMN %s TYPE %s',
                           $quotedTableName,
                           $this->quoteColumnName($columnName),
                           $this->typeToSql($type, $limit, $precision, $scale));
            $this->execute($sql);
        } catch (Horde_Db_Exception $e) {
            // This is PostgreSQL 7.x, or the old type could not be coerced to
            // the new type, so we have to use a more arcane way of doing it.
            try {
                // Booleans can't always be cast to other data types; do extra
                // work to handle them.
                $oldType = null;
                $columns = $this->columns($tableName);
                foreach ($this->columns($tableName) as $column) {
                    if ($column->getName() == $columnName) {
                        $oldType = $column->getType();
                        break;
                    }
                }
                if ($oldType === null) {
                    throw new Horde_Db_Exception("$tableName does not have a column '$columnName'");
                }

                $this->beginDbTransaction();

                $tmpColumnName = $columnName.'_change_tmp';
                $this->addColumn($tableName, $tmpColumnName, $type, $options);

                if ($oldType == 'boolean') {
                    $sql = sprintf('UPDATE %s SET %s = CAST(CASE WHEN %s IS TRUE THEN 1 ELSE 0 END AS %s)',
                                   $quotedTableName,
                                   $this->quoteColumnName($tmpColumnName),
                                   $this->quoteColumnName($columnName),
                                   $this->typeToSql($type, $limit, $precision, $scale));
                } else {
                    $sql = sprintf('UPDATE %s SET %s = CAST(%s AS %s)',
                                   $quotedTableName,
                                   $this->quoteColumnName($tmpColumnName),
                                   $this->quoteColumnName($columnName),
                                   $this->typeToSql($type, $limit, $precision, $scale));
                }
                $this->execute($sql);
                $this->removeColumn($tableName, $columnName);
                $this->renameColumn($tableName, $tmpColumnName, $columnName);

                $this->commitDbTransaction();
            } catch (Horde_Db_Exception $e) {
                $this->rollbackDbTransaction();
                throw $e;
            }
        }

        $default = isset($options['default']) ? $options['default'] : null;

        if ($autoincrement) {
            $seq_name = $this->defaultSequenceName($tableName, $columnName);
            try {
                $this->execute('DROP SEQUENCE ' . $seq_name . ' CASCADE');
            } catch (Horde_Db_Exception $e) {}
            $this->execute('CREATE SEQUENCE ' . $seq_name);

            /* Can't use changeColumnDefault() since it quotes the
             * default value (NEXTVAL is a postgres keyword, not a text
             * value). */
            $this->_clearTableCache($tableName);
            $sql = sprintf('ALTER TABLE %s ALTER COLUMN %s SET DEFAULT NEXTVAL(%s)',
                           $this->quoteTableName($tableName),
                           $this->quoteColumnName($columnName),
                           $this->quoteSequenceName($seq_name));
            $this->execute($sql);
        } elseif (array_key_exists('default', $options)) {
            $this->changeColumnDefault($tableName, $columnName, $default);
        }

        if (array_key_exists('null', $options)) {
            $this->changeColumnNull($tableName, $columnName, $options['null'], $default);
        }
    }

    /**
     * Changes the default value of a table column.
     */
    public function changeColumnDefault($tableName, $columnName, $default)
    {
        $this->_clearTableCache($tableName);
        $sql = sprintf('ALTER TABLE %s ALTER COLUMN %s SET DEFAULT %s',
                       $this->quoteTableName($tableName),
                       $this->quoteColumnName($columnName),
                       $this->quote($default));
        return $this->execute($sql);
    }

    public function changeColumnNull($tableName, $columnName, $null, $default = null)
    {
        $this->_clearTableCache($tableName);
        if (!($null || is_null($default))) {
            $sql = sprintf('UPDATE %s SET %s = %s WHERE %s IS NULL',
                           $this->quoteTableName($tableName),
                           $this->quoteColumnName($columnName),
                           $this->quote($default),
                           $this->quoteColumnName($columnName));
            $this->execute($sql);
        }
        $sql = sprintf('ALTER TABLE %s ALTER %s %s NOT NULL',
                       $this->quoteTableName($tableName),
                       $this->quoteColumnName($columnName),
                       $null ? 'DROP' : 'SET');
        return $this->execute($sql);
    }

    /**
     * Renames a column in a table.
     */
    public function renameColumn($tableName, $columnName, $newColumnName)
    {
        $this->_clearTableCache($tableName);
        $sql = sprintf('ALTER TABLE %s RENAME COLUMN %s TO %s',
                       $this->quoteTableName($tableName),
                       $this->quoteColumnName($columnName),
                       $this->quoteColumnName($newColumnName));
        return $this->execute($sql);
    }

    /**
     * Drops an index from a table.
     */
    public function removeIndex($tableName, $options = array())
    {
        $this->_clearTableCache($tableName);
        return $this->execute('DROP INDEX ' . $this->indexName($tableName, $options));
    }

    /**
     * Maps logical Rails types to PostgreSQL-specific data types.
     *
     * @throws Horde_Db_Exception
     */
    public function typeToSql($type, $limit = null, $precision = null,
                              $scale = null, $unsigned = null)
    {
        if ($type != 'integer') {
            return parent::typeToSql($type, $limit, $precision, $scale);
        }

        switch ($limit) {
        case 1:
        case 2:
            return 'smallint';

        case 3:
        case 4:
        case null:
            return 'integer';

        case 5:
        case 6:
        case 7:
        case 8:
            return 'bigint';
        }

        throw new Horde_Db_Exception("No integer type has byte size $limit. Use a numeric with precision 0 instead.");
    }

    /**
     * Returns a SELECT DISTINCT clause for a given set of columns and a given ORDER BY clause.
     *
     * PostgreSQL requires the ORDER BY columns in the select list for distinct queries, and
     * requires that the ORDER BY include the distinct column.
     *
     *   distinct("posts.id", "posts.created_at desc")
     */
    public function distinct($columns, $orderBy = null)
    {
        if (empty($orderBy)) {
            return 'DISTINCT ' . $columns;
        }

        // Construct a clean list of column names from the ORDER BY clause, removing
        // any ASC/DESC modifiers
        $orderColumns = array();
        foreach (preg_split('/\s*,\s*/', $orderBy, -1, PREG_SPLIT_NO_EMPTY) as $orderByClause) {
            $orderColumns[] = current(preg_split('/\s+/', $orderByClause, -1, PREG_SPLIT_NO_EMPTY)) . ' AS alias_' . count($orderColumns);
        }

        // Return a DISTINCT ON() clause that's distinct on the columns we want but includes
        // all the required columns for the ORDER BY to work properly.
        return sprintf('DISTINCT ON (%s) %s, %s',
                       $colummns, $columns, implode(', ', $orderColumns));
    }

    /**
     * Returns an ORDER BY clause for the passed order option.
     *
     * PostgreSQL does not allow arbitrary ordering when using DISTINCT ON, so we work around this
     * by wrapping the +sql+ string as a sub-select and ordering in that query.
     */
    public function addOrderByForAssociationLimiting($sql, $options)
    {
        if (empty($options['order'])) {
            return $sql;
        }

        $order = array();
        foreach (preg_split('/\s*,\s*/', $options['order'], -1, PREG_SPLIT_NO_EMPTY) as $s) {
            if (preg_match('/\bdesc$/i', $s)) $s = 'DESC';
            $order[] = 'id_list.alias_'.count($order).' '.$s;
        }
        $order = implode(', ', $order);

        return sprintf('SELECT * FROM (%s) AS id_list ORDER BY %s',
                       $sql, $order);
    }

    /**
     * Returns the list of a table's column names, data types, and default values.
     *
     * The underlying query is roughly:
     *   SELECT column.name, column.type, default.value
     *    FROM column LEFT JOIN default
     *      ON column.table_id = default.table_id
     *     AND column.num = default.column_num
     *   WHERE column.table_id = get_table_id('table_name')
     *     AND column.num > 0
     *     AND NOT column.is_dropped
     *   ORDER BY column.num
     *
     * If the table name is not prefixed with a schema, the database will
     * take the first match from the schema search path.
     *
     * Query implementation notes:
     *  - format_type includes the column size constraint, e.g. varchar(50)
     *  - ::regclass is a function that gives the id for a table name
     */
    public function columnDefinitions($tableName, $name = null)
    {
        /*@TODO See if we can get this from information_schema instead */
        return $this->selectAll('
            SELECT a.attname, format_type(a.atttypid, a.atttypmod), d.adsrc, a.attnotnull
              FROM pg_attribute a LEFT JOIN pg_attrdef d
                ON a.attrelid = d.adrelid AND a.attnum = d.adnum
             WHERE a.attrelid = ' . $this->quote($tableName) . '::regclass
               AND a.attnum > 0 AND NOT a.attisdropped
             ORDER BY a.attnum', $name);
    }

    /**
     * Returns the version of the connected PostgreSQL version.
     */
    public function postgresqlVersion()
    {
        try {
            $version = $this->selectValue('SELECT version()');
            if (preg_match('/PostgreSQL (\d+)\.(\d+)\.(\d+)/', $version, $matches))
                return ($matches[1] * 10000) + ($matches[2] * 100) + $matches[3];
        } catch (Exception $e) {}

        return 0;
    }
}
